(() => {
 // Home page banner slider. The active dot's fill animation is the timer: when it ends, the next banner shows.
 document.querySelectorAll('[data-banner-carousel]').forEach(root=>{
  const viewport=root.querySelector('.banner-viewport'),slides=[...root.querySelectorAll('.banner-slide')],dots=[...root.querySelectorAll('[data-banner-dot]')],pause=root.querySelector('[data-banner-pause]');
  const phone=matchMedia('(max-width: 700px)');let index=0,userPaused=false,hovering=false,focused=false;
  function ratioOf(slide){const image=slide.querySelector('img');let ratio=image?.naturalWidth?image.naturalWidth/image.naturalHeight:Number(slide.dataset.ratio)||2.4;return phone.matches?Math.min(ratio,2.6):ratio;}
  function fit(){viewport.style.setProperty('--ratio',ratioOf(slides[index]).toFixed(4));}
  function syncPause(){root.classList.toggle('is-paused',userPaused||hovering||focused);if(pause){pause.setAttribute('aria-pressed',String(userPaused));pause.setAttribute('aria-label',userPaused?'Play banners':'Pause banners');}}
  function show(next){
   index=(next+slides.length)%slides.length;
   slides.forEach((slide,i)=>{const active=i===index;slide.classList.toggle('is-active',active);slide.toggleAttribute('inert',!active);slide.setAttribute('aria-hidden',String(!active));});
   dots.forEach(dot=>dot.classList.remove('is-active'));void root.offsetWidth; // restart the fill animation
   dots.forEach((dot,i)=>{dot.classList.toggle('is-active',i===index);dot.setAttribute('aria-current',String(i===index));});
   fit();
  }
  slides.forEach(slide=>slide.querySelector('img')?.addEventListener('load',()=>{if(slide.classList.contains('is-active'))fit();}));
  phone.addEventListener?.('change',fit);fit();
  if(slides.length<2)return;
  root.querySelector('[data-banner-prev]')?.addEventListener('click',()=>show(index-1));
  root.querySelector('[data-banner-next]')?.addEventListener('click',()=>show(index+1));
  dots.forEach(dot=>dot.addEventListener('click',()=>show(Number(dot.dataset.bannerDot))));
  root.querySelector('.banner-dots')?.addEventListener('animationend',event=>{if(event.target.classList.contains('is-active'))show(index+1);});
  pause?.addEventListener('click',()=>{userPaused=!userPaused;syncPause();});
  root.addEventListener('pointerenter',event=>{if(event.pointerType==='mouse'){hovering=true;syncPause();}});
  root.addEventListener('pointerleave',()=>{hovering=false;syncPause();});
  root.addEventListener('focusin',()=>{focused=true;syncPause();});
  root.addEventListener('focusout',event=>{if(!root.contains(event.relatedTarget)){focused=false;syncPause();}});
  root.addEventListener('keydown',event=>{if(event.key==='ArrowLeft'){show(index-1);event.preventDefault();}if(event.key==='ArrowRight'){show(index+1);event.preventDefault();}});
  let startX=null,swiped=false;
  viewport.addEventListener('pointerdown',event=>{if(event.pointerType!=='mouse')startX=event.clientX;});
  viewport.addEventListener('pointerup',event=>{if(startX===null)return;const distance=event.clientX-startX;startX=null;if(Math.abs(distance)>40){swiped=true;show(index+(distance<0?1:-1));}});
  viewport.addEventListener('click',event=>{if(swiped){event.preventDefault();swiped=false;}},true);
  syncPause();
 });
})();
