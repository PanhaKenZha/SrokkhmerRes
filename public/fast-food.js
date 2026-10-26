document.querySelectorAll('.hot-feature').forEach(feature=>{
 const image=feature.querySelector('.hot-art'),button=feature.querySelector('.motion-toggle');
 const preference=window.matchMedia('(prefers-reduced-motion: reduce)');let playing=false;
 function setPlaying(value){playing=value;image.src=playing?image.dataset.animated:image.dataset.still;button.textContent=playing?'Pause animation':'Play animation';button.setAttribute('aria-label',button.textContent);}
 setPlaying(!preference.matches);
 button.addEventListener('click',()=>setPlaying(!playing));
 preference.addEventListener('change',event=>setPlaying(!event.matches));
});
