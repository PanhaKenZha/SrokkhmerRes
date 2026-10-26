(() => {
 document.querySelectorAll('.management-toolbar select').forEach(select=>select.addEventListener('change',()=>select.form.requestSubmit()));
 const panel=document.getElementById('management-preview'),form=document.getElementById('management-editor');if(!panel||!form)return;
 const kind=panel.dataset.previewKind,image=document.getElementById('preview-image'),reset=document.getElementById('preview-reset');
 const categoryInput=form.querySelector('[name="category"]');
 function markCategory(){document.querySelectorAll('[data-pick-category]').forEach(chip=>chip.classList.toggle('is-picked',chip.dataset.pickCategory.toLowerCase()===categoryInput?.value.trim().toLowerCase()));}
 document.querySelectorAll('[data-pick-category]').forEach(chip=>chip.addEventListener('click',()=>{categoryInput.value=chip.dataset.pickCategory;categoryInput.dispatchEvent(new Event('input',{bubbles:true}));}));
 function values(){const data={tags:[]};for(const input of form.querySelectorAll('input,textarea,select')){if(input.name==='tags[]'){if(input.checked)data.tags.push(input.dataset.label);}else if(input.type==='checkbox')data[input.name]=input.checked;else if(input.name&&!input.name.startsWith('_'))data[input.name]=input.value;}if(kind==='menu')data.restaurant=form.querySelector('[name="restaurant_id"]')?.selectedOptions[0]?.textContent;return data;}
 function markRow(row){document.querySelectorAll('.management-table tr.is-previewing').forEach(other=>other.classList.remove('is-previewing'));row?.classList.add('is-previewing');}
 function show(data,saved=false){
  panel.querySelectorAll('[data-preview-field]').forEach(element=>{const key=element.dataset.previewField;const fallback=key==='name'?(kind==='menu'?'Your dish name':'Your restaurant name'):key==='category'?'Category':'';element.textContent=data[key]||fallback;});
  const active=kind==='menu'?Boolean(data.is_available):Boolean(data.is_active),status=document.getElementById('preview-status');status.textContent=kind==='menu'?(active?'Available':'Unavailable'):(active?'Published':'Hidden');status.classList.toggle('is-live',active);
  document.getElementById('preview-context').textContent=saved?'Saved item':'Live from form';panel.classList.toggle('is-saved',saved);reset.hidden=!saved;if(!saved)markRow(null);
  if(kind==='menu'){
   const price=Number(data.price_usd);document.getElementById('preview-price').textContent=data.price_usd!==''&&Number.isFinite(price)&&price>=0?'$'+price.toFixed(2):'$0.00';document.getElementById('preview-restaurant').textContent=data.restaurant||'';
   const minutes=Number(data.prep_minutes);document.getElementById('preview-prep').textContent=data.prep_minutes&&Number.isFinite(minutes)&&minutes>0?`Ready in about ${Math.round(minutes)} min`:'';
   const tags=document.getElementById('preview-tags');tags.replaceChildren(...(data.tags||[]).map(label=>{const tag=document.createElement('span');tag.className='food-tag food-tag-'+label.toLowerCase().replace(/[^a-z]+/g,'-').replace(/^-|-$/g,'');tag.textContent=label;return tag;}));tags.hidden=!tags.children.length;markCategory();
   let url='';try{const parsed=new URL(data.image);if(parsed.protocol==='https:')url=parsed.href;}catch{}
   if(url){if(image.getAttribute('src')!==url){image.src=url;image.hidden=false;}}else{image.hidden=true;image.removeAttribute('src');}
  }else{
   const lat=Number(data.latitude),lng=Number(data.longitude);document.getElementById('preview-coordinates').textContent=data.latitude!==''&&data.longitude!==''&&data.latitude!=null&&data.longitude!=null&&Number.isFinite(lat)&&Number.isFinite(lng)?`${lat.toFixed(5)}, ${lng.toFixed(5)}`:'Choose a location on the map';
  }
  panel.querySelectorAll('.preview-meta,.preview-kh,.preview-description').forEach(line=>{line.hidden=!(line.matches('.preview-meta')?line.lastElementChild:line).textContent.trim();});
 }
 image?.addEventListener('error',()=>{image.hidden=true;});image?.addEventListener('load',()=>{image.hidden=false;});
 form.addEventListener('input',()=>show(values()));form.addEventListener('change',()=>show(values()));
 window.addEventListener('restaurant-location-selected',()=>show(values()));
 reset.addEventListener('click',()=>show(values()));
 document.querySelectorAll('[data-management-preview]').forEach(button=>button.addEventListener('click',()=>{try{show(JSON.parse(button.dataset.managementPreview),true);markRow(button.closest('tr'));panel.scrollIntoView({behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth',block:'nearest'});}catch{}}));
 document.addEventListener('error',event=>{if(event.target.matches?.('.table-thumbnail img'))event.target.remove();},true);
 show(values());
})();
