document.addEventListener('DOMContentLoaded', async () => {
    const form = document.getElementById('inspection-wizard');
    if (!form) return;
    const status = document.getElementById('draft-status'), state = document.getElementById('draft-state'), scope = form.dataset.scope;
    const token = form.elements.namedItem('_token').value;
    if(form.hasAttribute('data-tenant-draft')) form.addEventListener('submit',event=>{
        event.preventDefault();form.dispatchEvent(new Event('inspection-ready'));
    });
    let revision = Number(form.dataset.revision), dirty = false, busy = false, conflict = false, leaving = false, timer;
    const pickers = [...form.querySelectorAll('[data-inspection-photos]')];
    const pending = new Map();
    const cacheKey='inspection-draft:'+scope+':'+form.action;
    function setStatus(message, type = '') {
        status.textContent = message;
        if (state) state.className = 'inspection-save-state' + (type ? ' is-' + type : '');
    }
    async function preparePhoto(file) {
        if (!file.type.startsWith('image/') || file.type === 'image/gif') return file;
        try {
            const bitmap = await createImageBitmap(file);
            const scale = Math.min(1, 1600 / Math.max(bitmap.width, bitmap.height));
            if (scale === 1 && file.size < 1500 * 1024) { bitmap.close(); return file; }
            const canvas = document.createElement('canvas');
            canvas.width = Math.max(1, Math.round(bitmap.width * scale));
            canvas.height = Math.max(1, Math.round(bitmap.height * scale));
            canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
            bitmap.close();
            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', .82));
            return blob ? new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', {type:'image/jpeg', lastModified:file.lastModified}) : file;
        } catch (error) { return file; }
    }
    function cache() {
        try {localStorage.setItem(cacheKey,JSON.stringify({revision,fields:[...new FormData(form)].filter(([n,v])=>typeof v==='string'&&(n.startsWith('items[')||n.startsWith('inventory[')||n==='notes'))}));}
        catch(error) {setStatus('Device storage unavailable. Keep this page open.', 'error');}
    }
    try {
        const cached=JSON.parse(localStorage.getItem(cacheKey));
        if(cached && cached.revision===revision){
            cached.fields.forEach(([name,value])=>{
                const field=form.elements.namedItem(name);
                if(field) field.value=value;
            });
            dirty=true;
        }
    } catch(error) {}
    let db;
    const request = (method, value) => new Promise((resolve, reject) => {
        const tx = db.transaction('photos', method === 'getAll' ? 'readonly' : 'readwrite');
        const req = tx.objectStore('photos')[method](value);
        tx.oncomplete = () => resolve(req.result);
        tx.onerror = () => reject(tx.error);
        tx.onabort = () => reject(tx.error);
    });
    function render(picker) {
        const list = picker.querySelector('[data-photo-list]');
        list.querySelectorAll('img[data-local]').forEach(img => URL.revokeObjectURL(img.src));
        list.replaceChildren();
        const saved = JSON.parse(picker.dataset.saved);
        const queued = [...pending.values()].filter(p => p.item === picker.dataset.itemId);
        [...saved.map(p => ({url:p.url, label:'Saved'})), ...queued.map(p => ({url:URL.createObjectURL(p.file), label:'Waiting to upload', local:true}))].forEach(p => {
            const box = document.createElement('div'), img = document.createElement('img'), caption = document.createElement('small');
            img.src=p.url; img.alt='Inspection evidence'; img.width=80; img.height=80;
            if(p.local) img.dataset.local='1';
            caption.textContent=p.label; caption.style.display='block'; box.append(img,caption); list.append(box);
        });
        picker.querySelector('[data-photo-message]').textContent = saved.length+' saved · '+queued.length+' waiting';
    }
    async function send(url, body) {
        const response = await fetch(url, {method:'POST',headers:{'X-CSRF-TOKEN':token,Accept:'application/json'},body});
        if(response.status===409) conflict=true;
        if(!response.ok) {
            const data=await response.json().catch(()=>({}));
            throw new Error(data.message || 'Unable to save. Check your connection and retry.');
        }
        return response.json();
    }
    function payload() {
        const body=new FormData();
        for(const [name,value] of new FormData(form)) {
            if(typeof value==='string' && (name.startsWith('items[')||name.startsWith('inventory[')||name==='notes')) body.append(name,value);
        }
        body.append('revision',revision); body.append('step',form.dataset.step);
        return body;
    }
    async function flush() {
        if (conflict) return false;
        if (busy) {
            await new Promise(resolve => {
                const wait = setInterval(() => { if (!busy) { clearInterval(wait); resolve(); } }, 100);
            });
            return (dirty || pending.size) ? flush() : !conflict;
        }
        busy=true; setStatus(pending.size ? 'Uploading photos in background…' : 'Saving automatically…', 'busy');
        try {
            while(pending.size) {
                const p=pending.values().next().value, body=new FormData();
                body.append('upload_id',p.id);body.append('item_id',p.item);body.append('photo',p.file,p.file.name);
                const result=await send(form.dataset.photoUrl,body);
                if(db) await request('delete',p.id); pending.delete(p.id);
                const picker=pickers.find(x=>x.dataset.itemId===p.item);
                const saved=JSON.parse(picker.dataset.saved);
                if(!saved.some(x=>x.path===result.path)) saved.push(result);
                picker.dataset.saved=JSON.stringify(saved);render(picker);
            }
            while(dirty) {
                const body=payload();dirty=false;
                try {
                    const result=await send(form.dataset.draftUrl,body);
                    revision=result.revision;form.elements.namedItem('draft_revision').value=revision;
                    if(dirty) cache(); else try {localStorage.removeItem(cacheKey);} catch(error) {}
                } catch(error) {dirty=true;throw error;}
            }
            setStatus('All changes saved · '+new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}));
            return true;
        } catch(error) {
            setStatus(conflict?'Draft changed or closed. Reload before continuing.':error.message+' Work is kept on this device.', 'error');
            return false;
        } finally {busy=false;}
    }
    pickers.forEach(p=>render(p));
    try {
        db=await new Promise((resolve,reject)=>{
            const open=indexedDB.open('hhms-inspection-drafts',1);
            open.onupgradeneeded=()=>open.result.createObjectStore('photos',{keyPath:'id'});
            open.onsuccess=()=>resolve(open.result);open.onerror=()=>reject(open.error);
        });
        for(const p of await request('getAll')) if(p.scope===scope) pending.set(p.id,p);
    } catch(error) {setStatus('Local photo storage unavailable. Keep this page open during upload.', 'error');}
    pickers.forEach(picker=>{
        render(picker);
        picker.querySelectorAll('[data-photo-pick]').forEach(input=>input.addEventListener('change',async()=>{
            setStatus('Preparing photos…', 'busy');
            const originalFiles=[...input.files];input.value='';
            const files=[];
            for (const file of originalFiles) files.push(await preparePhoto(file));
            const count=JSON.parse(picker.dataset.saved).length+[...pending.values()].filter(p=>p.item===picker.dataset.itemId).length;
            if(count+files.length>5||files.some(f=>f.size>5*1024*1024||!['image/jpeg','image/png','image/webp'].includes(f.type))){
                picker.querySelector('[data-photo-message]').textContent='Maximum 5 JPG/PNG/WebP photos, 5 MB each.';setStatus('Some photos could not be added.', 'error');return;
            }
            for(const file of files){
                const p={id:crypto.randomUUID(),scope,item:picker.dataset.itemId,file};
                pending.set(p.id,p);
                if(db) try {await request('put',p);} catch(error){setStatus('Device storage full. Keep this page open until uploaded.', 'error');}
            }
            render(picker);flush();
        }));
    });
    form.addEventListener('input',()=>{dirty=true;cache();setStatus('Saving automatically…', 'busy');clearTimeout(timer);timer=setTimeout(flush,500);});
    window.addEventListener('online',flush);
    window.addEventListener('beforeunload',event=>{if(!leaving&&(dirty||pending.size||busy)){event.preventDefault();event.returnValue='';}});
    form.addEventListener('inspection-ready',async()=>{
        const button=document.getElementById('wizard-submit');button.disabled=true;
        dirty=true;
        if(await flush()){leaving=true;HTMLFormElement.prototype.submit.call(form);}
        else button.disabled=false;
    });
    if(pending.size||dirty) flush();
});
