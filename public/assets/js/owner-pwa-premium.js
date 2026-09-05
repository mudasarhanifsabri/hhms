(()=>{
  const placeholders={
    ar:{properties:'البحث عن الوحدة أو المبنى',bookings:'البحث عن الحجز أو الضيف أو الوحدة',transactions:'البحث في الوصف أو الوحدة أو الفئة',expenses:'البحث عن المصروف أو المورد أو الوحدة',maintenance:'البحث عن المهمة أو الوحدة أو الحالة',documents:'البحث عن المستند أو الوحدة'},
    en:{properties:'Search unit or building',bookings:'Search booking, guest or unit',transactions:'Search description, unit or category',expenses:'Search expense, vendor or unit',maintenance:'Search task, unit or status',documents:'Search document or unit'}
  };
  const translateInputs=()=>{const locale=document.documentElement.lang==='ar'?'ar':'en';document.querySelectorAll('[data-search]').forEach(input=>input.placeholder=placeholders[locale][input.dataset.search]||input.placeholder)};
  document.querySelector('[data-language-toggle]')?.addEventListener('click',()=>setTimeout(translateInputs));translateInputs();
  document.querySelectorAll('[data-search]').forEach(input=>input.addEventListener('input',()=>{
    const term=input.value.trim().toLocaleLowerCase();
    document.querySelectorAll(`[data-search-list="${input.dataset.search}"] [data-search-item]`).forEach(item=>item.hidden=term!==''&&!item.textContent.toLocaleLowerCase().includes(term));
  }));

  const calendar=document.querySelector('[data-calendar]');
  if(calendar){
    let cursor=new Date();cursor.setDate(1);
    let bookings=[];try{bookings=JSON.parse(calendar.dataset.bookings||'[]')}catch(error){}
    const dateKey=date=>`${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`;
    const render=()=>{
      const year=cursor.getFullYear(),month=cursor.getMonth(),days=new Date(year,month+1,0).getDate();
      const mondayOffset=(new Date(year,month,1).getDay()+6)%7;
      const names=['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
      calendar.innerHTML=names.map(name=>`<span class="head">${name}</span>`).join('')+'<span></span>'.repeat(mondayOffset);
      for(let day=1;day<=days;day++){
        const key=dateKey(new Date(year,month,day));
        const booked=bookings.some(item=>item.from<=key&&key<item.to);
        calendar.insertAdjacentHTML('beforeend',`<span class="${booked?'booked':''}">${day}</span>`);
      }
      const title=document.querySelector('[data-calendar-title]');if(title)title.textContent=cursor.toLocaleDateString(document.documentElement.lang||'en',{month:'long',year:'numeric'});
    };
    document.querySelector('[data-calendar-prev]')?.addEventListener('click',()=>{cursor.setMonth(cursor.getMonth()-1);render()});
    document.querySelector('[data-calendar-next]')?.addEventListener('click',()=>{cursor.setMonth(cursor.getMonth()+1);render()});
    render();
  }

  if('serviceWorker' in navigator)navigator.serviceWorker.addEventListener('controllerchange',()=>location.reload());
})();
