(() => {
 if(!window.SrokMaps){document.querySelectorAll('.location-map').forEach(el=>{el.textContent='Map could not load. Use the address or directions link.';});return;}
 document.querySelectorAll('[data-location-map]').forEach(element=>{
  const lat=Number(element.dataset.latitude),lng=Number(element.dataset.longitude);
  SrokMaps.mount(element,{center:[lat,lng],zoom:16,openFirst:true,pins:[{lat,lng,title:element.dataset.name,card:{title:element.dataset.name,address:element.dataset.address}}]});
 });
 const directory=document.getElementById('directory-map');
 if(directory){
  const locations=JSON.parse(document.getElementById('restaurant-locations-data').textContent);
  SrokMaps.mount(directory,{center:[11.5564,104.9282],zoom:12,fitPins:true,pins:locations.map(place=>({lat:Number(place.latitude),lng:Number(place.longitude),title:place.name,card:{title:place.name,address:place.address,link:{href:'/restaurants/'+place.id,label:'View menu'}}}))});
 }
 const picker=document.getElementById('location-picker');
 if(picker){
  const latitude=document.getElementById('restaurant-latitude'),longitude=document.getElementById('restaurant-longitude');
  const current=()=>latitude.value!==''&&longitude.value!==''&&latitude.checkValidity()&&longitude.checkValidity()?{lat:Number(latitude.value),lng:Number(longitude.value)}:null;
  function select(point){latitude.value=point.lat.toFixed(7);longitude.value=point.lng.toFixed(7);window.dispatchEvent(new CustomEvent('restaurant-location-selected'));}
  const start=current();
  const map=SrokMaps.mount(picker,{center:start?[start.lat,start.lng]:[11.5564,104.9282],zoom:start?17:13,picker:{title:'Restaurant entrance',initial:current,onPick:select}});
  for(const input of [latitude,longitude])input.addEventListener('change',()=>{const point=current();if(point)map.setPin(point);});
  document.getElementById('restaurant-geolocate').onclick=()=>{const message=document.getElementById('location-message');if(!navigator.geolocation){message.textContent='Location is unavailable. Click the map or enter coordinates.';return;}navigator.geolocation.getCurrentPosition(position=>{const point={lat:position.coords.latitude,lng:position.coords.longitude};select(point);map.setPin(point);message.textContent='Location selected. Check that the pin marks your restaurant entrance.';},()=>{message.textContent='Could not get your location. Click the map or enter coordinates.';},{timeout:10000});};
 }
})();
