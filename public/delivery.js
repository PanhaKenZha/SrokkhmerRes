(() => {
 const byId=id=>document.getElementById(id);
 let nearbyMap,deliveryMap,markers=[],fee=0,subtotal=0,revision=0,quoteExpires=0;
 const format=cents=>byId('currency')?.value==='KHR'?`${Math.round(cents/100*window.rielRate).toLocaleString()}៛`:`$${(cents/100).toFixed(2)}`;
 const message=(id,text)=>{if(byId(id))byId(id).textContent=text;};
 const position=()=>({lat:Number(window.mapsConfig.latitude),lng:Number(window.mapsConfig.longitude)});
 function total(){if(byId('checkout-total'))byId('checkout-total').textContent=`${window.uiText?.('Order total')||'Order total'}: ${format(subtotal+(byId('service').value==='Delivery'?fee:0))}`;}
 function invalidate(){revision++;fee=0;quoteExpires=0;if(byId('delivery-quote-id'))byId('delivery-quote-id').value='';message('delivery-quote-message','Get a new delivery quote before confirming your order.');total();}
 window.deliveryUI={renderTotal(value){subtotal=value;total();},reset:invalidate};
 async function api(url,options={}){
  const response=await fetch(url,{...options,headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,...options.headers}});
  const data=await response.json();if(!response.ok)throw Error(data.errors?Object.values(data.errors).flat().join(' '):data.message||'Please try again.');return data;
 }
 function geolocate(success,errorId){if(!navigator.geolocation){message(errorId,'Your browser does not support location. Enter coordinates or choose a pin.');return;}navigator.geolocation.getCurrentPosition(p=>success({lat:p.coords.latitude,lng:p.coords.longitude}),()=>message(errorId,'Location access failed. Choose a map location manually.'),{timeout:10000,maximumAge:60000});}
 function store(point){byId('delivery-latitude').value=point.lat.toFixed(7);byId('delivery-longitude').value=point.lng.toFixed(7);invalidate();}
 function pin(point){store(point);deliveryMap?.setPin(point);}
 function typedPoint(){const lat=byId('delivery-latitude'),lng=byId('delivery-longitude');return lat.value!==''&&lng.value!==''&&lat.checkValidity()&&lng.checkValidity()?{lat:Number(lat.value),lng:Number(lng.value)}:null;}
 function initDelivery(){
  if(!window.SrokMaps||!byId('delivery-map')||byId('delivery-fields').hidden||deliveryMap)return;
  const start=typedPoint()||position();
  deliveryMap=SrokMaps.mount(byId('delivery-map'),{center:[start.lat,start.lng],zoom:15,picker:{title:'Delivery entrance',initial:typedPoint,onPick:store}});
 }
 function initNearby(){
  if(!byId('restaurant-map')||nearbyMap||!window.SrokMaps?.googleReady())return;
  nearbyMap=new google.maps.Map(byId('restaurant-map'),{center:position(),zoom:14,mapId:window.mapsConfig.mapId||'DEMO_MAP_ID',streetViewControl:false,gestureHandling:'cooperative'});
  message('map-message','Move the map, then select Search this area.');
 }
 window.addEventListener('srok-google-maps-ready',initNearby);initNearby();
 window.addEventListener('srok-google-maps-failed',()=>{message('map-message','Google Maps authorization failed. Please contact the restaurant.');});
 if(byId('search-map'))byId('search-map').onclick=async()=>{
  if(!nearbyMap){message('map-message','The map is still loading or unavailable.');return;}
  const button=byId('search-map');button.disabled=true;message('map-message','Searching nearby restaurants…');
  markers.forEach(marker=>marker.map=null);markers=[];byId('restaurant-results').replaceChildren();
  try{
   const center=nearbyMap.getCenter();
   const data=await api(`/maps/restaurants?${new URLSearchParams({latitude:center.lat(),longitude:center.lng(),radius:byId('map-radius').value})}`);
   for(const place of data.places){
    const name=place.displayName?.text||'Restaurant';
    if(place.location){const marker=new google.maps.marker.AdvancedMarkerElement({map:nearbyMap,position:{lat:place.location.latitude,lng:place.location.longitude},title:name});markers.push(marker);}
    const card=document.createElement('article');card.className='order-card';
    const heading=document.createElement('h3');heading.textContent=name;card.append(heading);
    const address=document.createElement('p');address.textContent=place.formattedAddress||'';card.append(address);
    function addLink(url,label){try{const parsed=new URL(url);if(parsed.protocol!=='https:')return;const link=document.createElement('a');link.href=parsed.href;link.textContent=label;link.target='_blank';link.rel='noopener noreferrer';card.append(link);}catch{}}
    addLink(place.googleMapsUri,'View on Google Maps ↗');
    for(const attribution of place.attributions||[]){const p=document.createElement('p');p.className='muted';p.textContent=attribution.provider||'';card.append(p);if(attribution.providerUri)addLink(attribution.providerUri,'Data provider');}
    byId('restaurant-results').append(card);
   }
   message('map-message',`${data.places.length} nearby restaurants found. Results supplied by Google Maps; this is not a complete directory.`);
  }catch(error){message('map-message',error.message);}finally{button.disabled=false;}
 };
 if(byId('locate-me'))byId('locate-me').onclick=()=>geolocate(point=>{if(nearbyMap){nearbyMap.panTo(point);message('map-message','Location selected. Click Search this area.');}},'map-message');
 if(byId('delivery-fields')){
  byId('checkout').addEventListener('click',()=>{initDelivery();total();});
  byId('service').addEventListener('change',()=>{
   const delivery=byId('service').value==='Delivery';byId('delivery-fields').hidden=!delivery;byId('delivery-address').required=delivery;invalidate();if(delivery)initDelivery();
  });
  for(const id of ['delivery-address','delivery-latitude','delivery-longitude'])byId(id).addEventListener('input',invalidate);
  for(const id of ['delivery-latitude','delivery-longitude'])byId(id).addEventListener('change',()=>{const point=typedPoint();if(point)deliveryMap?.setPin(point);});
  byId('delivery-locate').onclick=()=>geolocate(pin,'delivery-quote-message');
  byId('quote-delivery').onclick=async()=>{
   invalidate();const currentRevision=revision;const button=byId('quote-delivery');
   const lat=byId('delivery-latitude'),lng=byId('delivery-longitude'),address=byId('delivery-address');
   if(!lat.value||!lng.value||!address.value.trim()||!lat.checkValidity()||!lng.checkValidity()){message('delivery-quote-message','Enter the address and valid coordinates, or choose a pin on the map.');return;}
   button.disabled=true;message('delivery-quote-message','Calculating road distance…');
   try{const quote=await api('/delivery/quote',{method:'POST',body:JSON.stringify({latitude:Number(lat.value),longitude:Number(lng.value),address:address.value,restaurant_id:byId('order-restaurant-id')?.value||null})});
    if(currentRevision!==revision)return;
    byId('delivery-quote-id').value=quote.quote_id;fee=quote.fee;quoteExpires=Date.now()+quote.expires_in*1000;
    message('delivery-quote-message',`Delivery ${format(fee)} · ${(quote.distance_meters/1000).toFixed(1)} km · about ${Math.ceil(quote.duration_seconds/60)} minutes driving. Quote valid for 10 minutes.`);total();
   }catch(error){message('delivery-quote-message',error.message);}finally{button.disabled=false;}
  };
  byId('order-form').addEventListener('submit',event=>{
   if(byId('service').value==='Delivery'&&(!byId('delivery-quote-id').value||Date.now()>quoteExpires)){
    event.preventDefault();event.stopImmediatePropagation();message('order-error','Calculate a current delivery fee before confirming.');
   }
  },true);
 }
})();
