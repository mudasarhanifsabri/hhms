document.addEventListener('DOMContentLoaded', async () => {
    const form = document.getElementById('inspection-wizard');
    if (!form) return;
    const status = document.getElementById('draft-status'), scope = form.dataset.scope;
    const token = form.elements.namedItem('_token').value;
    if(form.hasAttribute('data-tenant-draft')) form.addEventListener('submit',event=>{
        event.preventDefault();form.dispatchEvent(new Event('inspection-ready'));
    });
    let revision = Number(form.dataset.revision), dirty = false, busy = false, conflict = false, leaving = false, timer;
    const pickers = [...form.querySelectorAll('[data-inspection-photos]')];
    const pending = new Map();
    const cacheKey='inspection-draft:'+scope+':'+form.action;
    function cache() {
        try {localStorage.setItem(cacheKey,JSON.stringify({revision,fields:[...new FormData(form)].filter(([n,v])=>typeof v==='string'&&(n.startsWith('items[')||n.startsWith('inventory[')||n==='notes'))}));}
        catch(error) {status.textContent='Device storage unavailable. Save before leaving.';}
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
        if(busy || conflict) return false;
        busy=true;
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
            status.textContent='Saved to server · '+new Date().toLocaleTimeString();
            return true;
        } catch(error) {
            status.textContent=conflict?'Draft changed or closed. Reload before continuing.':error.message+' Your unsaved work remains on this screen.';
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
    } catch(error) {status.textContent='Local photo storage unavailable. Keep this page open until uploads finish.';}
    pickers.forEach(picker=>{
        render(picker);
        picker.querySelectorAll('[data-photo-pick]').forEach(input=>input.addEventListener('change',async()=>{
            const files=[...input.files];input.value='';
            const count=JSON.parse(picker.dataset.saved).length+[...pending.values()].filter(p=>p.item===picker.dataset.itemId).length;
            if(count+files.length>5||files.some(f=>f.size>5*1024*1024||!['image/jpeg','image/png','image/webp'].includes(f.type))){
                picker.querySelector('[data-photo-message]').textContent='Maximum 5 JPG/PNG/WebP photos, 5 MB each.';return;
            }
            for(const file of files){
                const p={id:crypto.randomUUID(),scope,item:picker.dataset.itemId,file};
                pending.set(p.id,p);
                if(db) try {await request('put',p);} catch(error){status.textContent='Device storage full. Keep this page open until uploaded.';}
            }
            render(picker);flush();
        }));
    });
    form.addEventListener('input',()=>{dirty=true;cache();status.textContent='Saving…';clearTimeout(timer);timer=setTimeout(flush,700);});
    document.getElementById('draft-save').addEventListener('click',()=>{dirty=true;flush();});
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
