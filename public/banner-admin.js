(() => {
 // Live preview for the manager Banners form.
 const form=document.getElementById('banner-editor');if(!form)return;
 const file=document.getElementById('banner-file'),drop=document.getElementById('banner-drop'),fileName=document.getElementById('banner-file-name');
 const viewport=document.getElementById('banner-preview'),image=document.getElementById('banner-preview-image'),empty=document.getElementById('banner-preview-empty');
 const cta=document.getElementById('banner-preview-cta'),ctaText=document.getElementById('banner-preview-cta-text'),state=document.getElementById('banner-preview-state');
 const url=form.querySelector('[name="image_url"]'),link=form.querySelector('[name="link_url"]'),label=form.querySelector('[name="button_label"]'),title=form.querySelector('[name="title"]');
 let objectUrl=null;
 function showImage(src){if(!src){return;}image.src=src;image.hidden=false;empty.hidden=true;state.textContent='Live from form';}
 image.addEventListener('load',()=>{if(image.naturalWidth)viewport.style.setProperty('--ratio',(image.naturalWidth/image.naturalHeight).toFixed(4));});
 image.addEventListener('error',()=>{image.hidden=true;empty.hidden=false;});
 file.addEventListener('change',()=>{const chosen=file.files[0];fileName.textContent=chosen?chosen.name:'';drop.classList.toggle('has-file',Boolean(chosen));if(!chosen)return;if(objectUrl)URL.revokeObjectURL(objectUrl);objectUrl=URL.createObjectURL(chosen);showImage(objectUrl);});
 url.addEventListener('input',()=>{try{const parsed=new URL(url.value);if(parsed.protocol==='https:'&&!file.files.length)showImage(parsed.href);}catch{}});
 function syncCta(){ctaText.textContent=label.value.trim();cta.hidden=!(label.value.trim()&&link.value.trim());image.alt=title.value;}
 [link,label,title].forEach(input=>input.addEventListener('input',syncCta));
 document.querySelectorAll('[data-pick-link]').forEach(chip=>chip.addEventListener('click',()=>{link.value=chip.dataset.pickLink;syncCta();document.querySelectorAll('[data-pick-link]').forEach(other=>other.classList.toggle('is-picked',other===chip));}));
 ['dragenter','dragover'].forEach(type=>drop.addEventListener(type,event=>{event.preventDefault();drop.classList.add('is-dragging');}));
 ['dragleave','drop'].forEach(type=>drop.addEventListener(type,()=>drop.classList.remove('is-dragging')));
 drop.addEventListener('drop',event=>{event.preventDefault();if(event.dataTransfer.files.length){file.files=event.dataTransfer.files;file.dispatchEvent(new Event('change'));}});
 syncCta();
})();
