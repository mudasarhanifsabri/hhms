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
        list.replaceChildren();
        const saved = JSON.parse(picker.dataset.saved);
        const queued = [...pending.values()].filter(p => p.item === picker.dataset.itemId);
        [...saved.map(p => ({...p, label:'Saved', saved:true})), ...queued.map(p => ({...p, label:'Waiting to upload', saved:false}))].forEach(p => {
            const box = document.createElement('div'), caption = document.createElement('small'), remove = document.createElement('button');
            box.style.position='relative';
            const icon = document.createElement(p.url ? 'a' : 'span');
            icon.className='d-flex align-items-center justify-content-center border rounded';
            icon.style.width='80px';icon.style.height='80px';icon.textContent=p.url ? 'View photo' : 'Photo';
            if (p.url) {icon.href=p.url;icon.target='_blank';icon.rel='noopener';}
            remove.type='button';remove.className='btn btn-danger btn-sm';remove.setAttribute('aria-label','Delete this photo');remove.title='Delete photo';
            remove.innerHTML='<i class="ri-delete-bin-line"></i>';remove.style.cssText='position:absolute;right:-7px;top:-7px;border-radius:50%;width:30px;height:30px;padding:0;z-index:2';
            remove.addEventListener('click',async()=>{
                remove.disabled=true;
                if(!p.saved){pending.delete(p.id);if(db) await request('delete',p.id);render(picker);setStatus('Selected photo removed.');return;}
                try{
                    setStatus('Deleting photo…','busy');
                    const body=new FormData();body.append('item_id',picker.dataset.itemId);body.append('path',p.path);
                    await send(form.dataset.photoDeleteUrl,body,'DELETE');
                    picker.dataset.saved=JSON.stringify(JSON.parse(picker.dataset.saved).filter(photo=>photo.path!==p.path));
                    render(picker);setStatus('Photo deleted.');
                }catch(error){remove.disabled=false;setStatus(error.message,'error');}
            });
            box.append(icon);
            box.append(remove);
            caption.textContent=p.label; caption.style.display='block'; box.append(caption); list.append(box);
        });
        picker.querySelector('[data-photo-message]').textContent = saved.length+' saved · '+queued.length+' waiting';
    }
    async function send(url, body, method = 'POST') {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 45000);
        let response;
        try {
            response = await fetch(url, {method,headers:{'X-CSRF-TOKEN':token,Accept:'application/json'},body,signal:controller.signal});
        } catch(error) {
            throw new Error(error.name === 'AbortError' ? 'Upload timed out. Tap Retry upload.' : 'Connection lost. Tap Retry upload.');
        } finally {clearTimeout(timeout);}
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
            if(retryButton) retryButton.hidden=true;
            return true;
        } catch(error) {
            setStatus(conflict?'Draft changed or closed. Reload before continuing.':error.message+' Work is kept on this device.', 'error');
            if(retryButton) retryButton.hidden=conflict;
            return false;
        } finally {busy=false;}
    }
    const retryButton=document.getElementById('inspection-retry');
    pickers.forEach(picker=>{
        render(picker);
        picker.querySelectorAll('[data-photo-pick]').forEach(input=>input.addEventListener('change',async()=>{
            const originalFiles=[...input.files];input.value='';
            const count=JSON.parse(picker.dataset.saved).length+[...pending.values()].filter(p=>p.item===picker.dataset.itemId).length;
            if(count+originalFiles.length>10||originalFiles.some(f=>f.size>10*1024*1024||!['image/jpeg','image/png','image/webp'].includes(f.type))){
                picker.querySelector('[data-photo-message]').textContent='Maximum 10 JPG/PNG/WebP photos per item, 10 MB each.';setStatus('Photo could not be added. Use a JPG, PNG or WebP under 10 MB.', 'error');return;
            }
            setStatus('Uploading photos…', 'busy');
            for(const file of originalFiles){
                const p={id:crypto.randomUUID(),scope,item:picker.dataset.itemId,file};
                pending.set(p.id,p);
                if(db) try {await request('put',p);} catch(error){setStatus('Device storage full. Keep this page open until uploaded.', 'error');}
            }
            render(picker);flush();
        }));
    });
    form.addEventListener('input',()=>{dirty=true;cache();setStatus('Saving automatically…', 'busy');clearTimeout(timer);timer=setTimeout(flush,500);});
    retryButton?.addEventListener('click',()=>{retryButton.hidden=true;flush();});
    document.getElementById('draft-save')?.addEventListener('click',()=>{dirty=true;flush();});
    window.addEventListener('online',flush);
    window.addEventListener('beforeunload',event=>{if(!leaving&&(dirty||pending.size||busy)){event.preventDefault();event.returnValue='';}});
    form.addEventListener('inspection-ready',async()=>{
        const button=document.getElementById('wizard-submit');button.disabled=true;
        dirty=true;
        if(await flush()){leaving=true;HTMLFormElement.prototype.submit.call(form);}
        else button.disabled=false;
    });
    try {
        db=await new Promise((resolve,reject)=>{
            const open=indexedDB.open('hhms-inspection-drafts',1);
            const timeout=setTimeout(()=>reject(new Error('Storage timed out')),5000);
            open.onupgradeneeded=()=>open.result.createObjectStore('photos',{keyPath:'id'});
            open.onsuccess=()=>{clearTimeout(timeout);resolve(open.result);};
            open.onerror=()=>{clearTimeout(timeout);reject(open.error);};
            open.onblocked=()=>{clearTimeout(timeout);reject(new Error('Storage blocked'));};
        });
        for(const p of await request('getAll')) if(p.scope===scope) pending.set(p.id,p);
        pickers.forEach(render);
    } catch(error) {setStatus('Local photo storage unavailable. Keep this page open during upload.', 'error');}
    if(pending.size||dirty) flush();
});
