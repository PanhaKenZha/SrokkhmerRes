(() => {
 const dictionary=window.khmerTranslations||{};
 let language=document.documentElement.lang==='km'?'km':'en';
 const textSources=new WeakMap(),attributeSources=new WeakMap();let pending=false;
 const excluded='script,style,textarea,code,pre,.leaflet-container,.gm-style,[data-no-translate]';
 function translate(text){return language==='km'?(dictionary[text]||text):text;}
 window.uiText=translate;
 function apply(){
  pending=false;
  // Set option values before translating labels so checkout and filters keep their API values.
  document.querySelectorAll('select option:not([value])').forEach(option=>{option.value=option.textContent;});
  const walker=document.createTreeWalker(document.body,NodeFilter.SHOW_TEXT);
  while(walker.nextNode()){
   const node=walker.currentNode;if(!node.parentElement||node.parentElement.closest(excluded))continue;
   let record=textSources.get(node);if(!record||node.nodeValue!==record.last)record={source:node.nodeValue,last:node.nodeValue};
   const key=record.source.trim(),value=record.source.replace(key,translate(key));
   if(node.nodeValue!==value)node.nodeValue=value;
   record.last=value;textSources.set(node,record);
  }
  document.querySelectorAll('[placeholder],[aria-label],[title]').forEach(element=>{
   if(element.closest(excluded))return;
   let records=attributeSources.get(element)||{};
   for(const name of ['placeholder','aria-label','title']){
    if(!element.hasAttribute(name))continue;const current=element.getAttribute(name);
    let record=records[name];if(!record||current!==record.last)record={source:current,last:current};
    const value=translate(record.source);if(current!==value)element.setAttribute(name,value);record.last=value;records[name]=record;
   }
   attributeSources.set(element,records);
  });
 }
 function schedule(){if(!pending){pending=true;requestAnimationFrame(apply);}}
 function setLanguage(value,notify=true){
  language=value==='km'?'km':'en';document.documentElement.lang=language;
  try{localStorage.setItem('srok-language',language);}catch{}
  for(const id of ['site-language','language']){const input=document.getElementById(id);if(input)input.value=language;}
  document.querySelectorAll('[data-set-language]').forEach(button=>button.setAttribute('aria-pressed',String(button.dataset.setLanguage===language)));
  if(notify)window.dispatchEvent(new CustomEvent('site-language-change',{detail:language}));
  apply();
 }
 const themeButton=document.getElementById('theme-toggle');
 function reflectTheme(){const dark=document.documentElement.dataset.theme==='dark';if(themeButton){themeButton.setAttribute('aria-checked',String(dark));const icon=document.getElementById('theme-icon');if(icon)icon.textContent=dark?'☀':'☾';}}
 let themeTimer;
 themeButton?.addEventListener('click',()=>{const root=document.documentElement,theme=root.dataset.theme==='dark'?'light':'dark';root.classList.add('theme-switching');clearTimeout(themeTimer);themeTimer=setTimeout(()=>root.classList.remove('theme-switching'),450);root.dataset.theme=theme;try{localStorage.setItem('srok-theme',theme);}catch{}reflectTheme();});
 document.querySelectorAll('[data-set-language]').forEach(button=>button.addEventListener('click',()=>setLanguage(button.dataset.setLanguage)));
 document.getElementById('site-language')?.addEventListener('change',event=>setLanguage(event.target.value));
 document.getElementById('language')?.addEventListener('change',event=>setLanguage(event.target.value));
 setLanguage(language,false);reflectTheme();
 new MutationObserver(schedule).observe(document.body,{childList:true,subtree:true,characterData:true});
})();
