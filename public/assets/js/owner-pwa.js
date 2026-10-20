(()=>{
  const app=document.querySelector('[data-owner-app]');if(!app)return;
  const screens=[...document.querySelectorAll('[data-screen]')];
  const welcome=document.querySelector('[data-owner-welcome]');
  const welcomeKey=`owner-welcome-v2:${app.dataset.ownerId||'current'}`;
  const arabic={'Install Owner App':'تثبيت تطبيق المالك','Good morning,':'صباح الخير،','Good afternoon,':'مساء الخير،','Good evening,':'مساء الخير،','Gross revenue':'إجمالي الإيرادات','Expenses':'المصروفات','Management fee':'رسوم الإدارة','Net income':'صافي الدخل','Properties':'العقارات','Booked nights':'الليالي المحجوزة','Occupancy':'نسبة الإشغال','Payable':'المبلغ المستحق','Upcoming Booking':'الحجز القادم','View Booking':'عرض الحجز','No upcoming bookings':'لا توجد حجوزات قادمة','My Properties':'عقاراتي','No properties assigned':'لا توجد عقارات مسندة','Property Details':'تفاصيل العقار','Active':'نشط','Bedrooms':'غرف النوم','Bathrooms':'الحمامات','Area':'المساحة','Performance This Month':'أداء هذا الشهر','Revenue':'الإيرادات','Net Income':'صافي الدخل','Calendar':'التقويم','Utilities':'الخدمات','Maintenance':'الصيانة','Documents':'المستندات','Statement':'كشف الحساب','Bookings':'الحجوزات','No bookings yet':'لا توجد حجوزات بعد','Booking Details':'تفاصيل الحجز','Guest':'الضيف','Check-in':'تسجيل الوصول','Check-out':'تسجيل المغادرة','Nights':'الليالي','Guests':'الضيوف','Total Amount':'المبلغ الإجمالي','Payment Status':'حالة الدفع','Booking Calendar':'تقويم الحجوزات','Finance':'المالية','Net Payable':'صافي المستحق','View Statement':'عرض كشف الحساب','Transactions':'المعاملات','No transactions':'لا توجد معاملات','No utility bills':'لا توجد فواتير خدمات','All':'الكل','In Progress':'قيد التنفيذ','Completed':'مكتمل','No maintenance requests':'لا توجد طلبات صيانة','My Documents':'مستنداتي','No documents':'لا توجد مستندات','Notifications':'الإشعارات','No notifications':'لا توجد إشعارات','Payouts':'الدفعات','No payouts recorded':'لا توجد دفعات مسجلة','Owner':'المالك','Desktop Owner Portal':'بوابة المالك على الكمبيوتر','My Information':'معلوماتي','Bank Details':'التفاصيل البنكية','Change Password':'تغيير كلمة المرور','Logout':'تسجيل الخروج','Current Password':'كلمة المرور الحالية','New Password':'كلمة المرور الجديدة','Confirm Password':'تأكيد كلمة المرور','Update Password':'تحديث كلمة المرور','Password updated successfully.':'تم تحديث كلمة المرور بنجاح.','Home':'الرئيسية','Profile':'الملف الشخصي','Paid':'مدفوع','Pending':'قيد الانتظار','Approved':'معتمد','Confirmed':'مؤكد','Available':'متاح','Owner Paid':'دفع المالك'};
  arabic['Document Wallet']='محفظة المستندات';arabic['No expiry']='بدون تاريخ انتهاء';arabic['Valid']='ساري';arabic['Expired']='منتهي';arabic['Expiring']='قارب على الانتهاء';arabic['Owner Expenses']='مصروفات المالك';arabic['Received rental income']='إيرادات الإيجار المستلمة';arabic['Owner expenses']='مصروفات المالك';arabic['Download Statement']='تحميل كشف الحساب';arabic['Search unit or building']='البحث عن الوحدة أو المبنى';arabic['Search booking, guest or unit']='البحث عن الحجز أو الضيف أو الوحدة';arabic['Search description, unit or category']='البحث في الوصف أو الوحدة أو الفئة';arabic['Search expense, vendor or unit']='البحث عن المصروف أو المورد أو الوحدة';arabic['Search task, unit or status']='البحث عن المهمة أو الوحدة أو الحالة';arabic['Search document or unit']='البحث عن المستند أو الوحدة';arabic['Mark all read']='تحديد الكل كمقروء';arabic['Full name']='الاسم الكامل';arabic['Email']='البريد الإلكتروني';arabic['Phone']='الهاتف';arabic['Nationality']='الجنسية';arabic['Passport / Emirates ID']='جواز السفر / الهوية الإماراتية';arabic['Address']='العنوان';arabic['Account holder']='صاحب الحساب';arabic['Bank']='البنك';arabic['Account number']='رقم الحساب';arabic['Branch']='الفرع';
  arabic['Owner Booking Details']='تفاصيل حجز المالك';arabic['Booking periods']='فترات الحجز';arabic['Original Booking']='الحجز الأصلي';arabic['Extension']='التمديد';arabic['Renewal']='التجديد';arabic['Rent collected']='الإيجار المحصل';arabic['Expected owner payout']='الدفعة المتوقعة للمالك';arabic['Payout due']='تاريخ استحقاق الدفعة';arabic['Upcoming payout']='دفعة قادمة';arabic['Awaiting guest payment']='بانتظار دفعة الضيف';arabic['Ready for payout']='جاهز للدفع';arabic['Partially paid']='مدفوع جزئياً';arabic['Paid to owner']='تم الدفع للمالك';arabic['Remaining']='المتبقي';arabic['Payment reference']='مرجع الدفع';arabic['Total rent collected']='إجمالي الإيجار المحصل';arabic['Total management fee']='إجمالي رسوم الإدارة';arabic['Total expected payout']='إجمالي الدفعة المتوقعة';
  Object.assign(arabic,{'Warm greetings from Sultan':'أطيب التحيات من سلطان','Quick access':'وصول سريع','Upcoming bookings':'الحجوزات القادمة','View all':'عرض الكل','Occupied':'مشغولة','Vacant':'شاغرة','Property':'العقار','Size':'المساحة','Owner Performance':'أداء العقار','Net booking income':'صافي دخل الحجز','Monthly':'شهري','All time':'كل الفترات','Statement month':'شهر كشف الحساب','This month':'هذا الشهر','Amount to recover':'مبلغ مستحق التحصيل','Total credits':'إجمالي الدائن','Total debits':'إجمالي المدين','Income':'الدخل','Balance':'الرصيد','Balance after transaction':'الرصيد بعد المعاملة','Description':'الوصف','Reference':'المرجع','Unit':'الوحدة','Payment method':'طريقة الدفع','Account':'الحساب','Not recorded':'غير مسجل','All units':'كل الوحدات','No additional description':'لا يوجد وصف إضافي','No bookings found':'لا توجد حجوزات','No invoice periods recorded':'لا توجد فترات فواتير مسجلة','Booked':'محجوز','Booking schedule':'جدول الحجوزات','Review & Decide':'مراجعة واتخاذ قرار','No owner expenses':'لا توجد مصروفات على المالك','Opened':'تاريخ الفتح','Due':'الاستحقاق','Cost':'التكلفة','No description provided.':'لا يوجد وصف.','No maintenance tasks':'لا توجد مهام صيانة','You are all caught up':'لا توجد إشعارات جديدة','Not provided':'غير متوفر','Owner Payout Transfer':'تحويل دفعة للمالك','Credit Adjustment':'تسوية دائنة','Debit Adjustment':'تسوية مدينة','Rent Income':'دخل الإيجار','Internet':'الإنترنت','Cleaning':'التنظيف','Furnishing / Setup Cost':'تكلفة التأثيث / التجهيز','Other Expense':'مصروف آخر','Contact management to update verified payout details.':'تواصل مع الإدارة لتحديث بيانات الدفع الموثقة.'});
  const arabicMonths={January:'يناير',February:'فبراير',March:'مارس',April:'أبريل',May:'مايو',June:'يونيو',July:'يوليو',August:'أغسطس',September:'سبتمبر',October:'أكتوبر',November:'نوفمبر',December:'ديسمبر',Jan:'يناير',Feb:'فبراير',Mar:'مارس',Apr:'أبريل',Jun:'يونيو',Jul:'يوليو',Aug:'أغسطس',Sep:'سبتمبر',Oct:'أكتوبر',Nov:'نوفمبر',Dec:'ديسمبر'};
  const translateValue=(source,locale)=>{if(locale!=='ar')return source;const trimmed=source.trim(),translated=arabic[trimmed];if(translated)return source.replace(trimmed,translated);let value=source.replace(/\bAED\b/g,'د.إ').replace(/\bunits\b/gi,'وحدات').replace(/^Balance:/,'الرصيد:').replace(/^Management fee/,'رسوم الإدارة').replace(/^Approval:/,'الموافقة:');Object.entries(arabicMonths).forEach(([english,arabicMonth])=>{value=value.replace(new RegExp(`\\b${english}\\b`,'g'),arabicMonth);});return value;};
  const translate=locale=>{
    document.documentElement.lang=locale;document.documentElement.dir=locale==='ar'?'rtl':'ltr';
    const walker=document.createTreeWalker(app,NodeFilter.SHOW_TEXT);
    while(walker.nextNode()){
      const node=walker.currentNode;if(!node.parentElement||['SCRIPT','STYLE'].includes(node.parentElement.tagName))continue;
      if(node._ownerOriginal===undefined)node._ownerOriginal=node.nodeValue;
      node.nodeValue=translateValue(node._ownerOriginal,locale);
    }
    app.querySelectorAll('[placeholder],[title],[aria-label]').forEach(element=>['placeholder','title','aria-label'].forEach(attribute=>{
      if(!element.hasAttribute(attribute))return;
      const key=`ownerOriginal${attribute.replace('-','')}`;
      if(element.dataset[key]===undefined)element.dataset[key]=element.getAttribute(attribute);
      element.setAttribute(attribute,translateValue(element.dataset[key],locale));
    }));
    const label=document.querySelector('[data-language-label]');if(label)label.textContent=locale==='ar'?'English':'العربية';localStorage.setItem('owner-locale',locale);
  };
  const open=(name,push=true)=>{
    const target=document.querySelector(`[data-screen="${name}"]`)||document.querySelector('[data-screen="home"]');
    screens.forEach(screen=>screen.classList.toggle('is-active',screen===target));
    document.querySelectorAll('[data-nav]').forEach(item=>item.classList.toggle('is-active',item.dataset.nav===name));
    target.scrollTop=0; if(push&&location.hash!==`#${name}`)history.pushState({},'',`#${name}`);
  };
  document.addEventListener('click',event=>{const language=event.target.closest('[data-language-toggle]');if(language){translate(document.documentElement.lang==='ar'?'en':'ar');return;}const link=event.target.closest('[data-go]');if(link){event.preventDefault();open(link.dataset.go);}});
  addEventListener('popstate',()=>open(location.hash.slice(1)||'home',false));
  addEventListener('hashchange',()=>open(location.hash.slice(1)||'home',false));
  open(location.hash.slice(1)||'home',false);translate(localStorage.getItem('owner-locale')==='ar'?'ar':'en');
  requestAnimationFrame(()=>app.classList.add('is-ready'));
  let welcomeLeaveTimer,welcomeHideTimer;
  const dubaiGreeting=()=>{
    const hour=Number(new Intl.DateTimeFormat('en-GB',{timeZone:'Asia/Dubai',hour:'2-digit',hourCycle:'h23'}).format(new Date()));
    const period=hour<12?'morning':hour<17?'afternoon':'evening';
    const isArabic=document.documentElement.lang==='ar';
    const labels=isArabic?{morning:'صباح الخير',afternoon:'مساء الخير',evening:'مساء الخير'}:{morning:'Good morning',afternoon:'Good afternoon',evening:'Good evening'};
    return {period,label:labels[period]};
  };
  const refreshGreeting=()=>{
    const greeting=dubaiGreeting();
    app.dataset.greetingPeriod=greeting.period;
    document.querySelectorAll('[data-greeting-text]').forEach(node=>node.textContent=`${greeting.label},`);
    try{
      if(!welcome||localStorage.getItem(welcomeKey)===greeting.period)return;
      clearTimeout(welcomeLeaveTimer);clearTimeout(welcomeHideTimer);
      welcome.classList.remove('is-leaving');welcome.setAttribute('aria-hidden','false');
      requestAnimationFrame(()=>welcome.classList.add('is-visible'));
      welcomeLeaveTimer=setTimeout(()=>welcome.classList.add('is-leaving'),2200);
      welcomeHideTimer=setTimeout(()=>{welcome.classList.remove('is-visible','is-leaving');welcome.setAttribute('aria-hidden','true');localStorage.setItem(welcomeKey,greeting.period);},2850);
    }catch(error){welcome?.classList.remove('is-visible');}
  };
  refreshGreeting();setInterval(refreshGreeting,60000);
  const periodForm=document.querySelector('.oa-period-filter');
  if(periodForm){periodForm.action=`${periodForm.action}#finance`;periodForm.querySelectorAll('button[name="statement_scope"]').forEach(button=>button.addEventListener('click',()=>{const hidden=periodForm.querySelector('input[type="hidden"][name="statement_scope"]');if(hidden)hidden.value=button.value;}));}
  document.querySelectorAll('[data-filter]').forEach(button=>button.addEventListener('click',()=>{const group=button.closest('[data-filter-group]');group.querySelectorAll('[data-filter]').forEach(x=>x.classList.remove('is-active'));button.classList.add('is-active');group.parentElement.querySelectorAll('[data-status]').forEach(row=>row.hidden=button.dataset.filter!=='all'&&row.dataset.status!==button.dataset.filter);}));
  const install=document.querySelector('[data-install]');let prompt;addEventListener('beforeinstallprompt',e=>{e.preventDefault();prompt=e;install?.classList.add('show');});install?.addEventListener('click',async()=>{if(prompt){prompt.prompt();await prompt.userChoice;prompt=null;install.classList.remove('show');}});
  if('serviceWorker' in navigator)navigator.serviceWorker.register(window.ownerApp.sw,{updateViaCache:'none'}).then(registration=>registration.update()).catch(()=>{});
})();
