(() => {
 // One map API for every page. Uses real Google Maps when a browser key is configured and accepted;
 // otherwise a Google-style Leaflet map (streets/satellite, red pins, info cards, Google directions links).
 const config=()=>window.mapsConfig||{};
 const googleLinks={
  search:(lat,lng)=>`https://www.google.com/maps/search/?api=1&query=${lat},${lng}`,
  directions:(lat,lng)=>`https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`,
 };
 let state=config().google?'loading':'off';
 const queue=[],mounted=[];
 function settle(next){if(state!=='loading')return;state=next;queue.splice(0).forEach(run=>run());}
 window.srokGoogleMapsReady=()=>{settle('ready');window.dispatchEvent(new Event('srok-google-maps-ready'));};
 window.gm_authFailure=()=>{state='failed';window.dispatchEvent(new Event('srok-google-maps-failed'));mounted.filter(record=>record.engine==='google').forEach(record=>record.remount());};
 if(state==='loading')setTimeout(()=>settle('off'),8000);
 const googleReady=()=>state==='ready'&&Boolean(window.google?.maps?.Map);
 const dark=()=>document.documentElement.dataset.theme==='dark';
 const point=value=>Array.isArray(value)?{lat:Number(value[0]),lng:Number(value[1])}:{lat:Number(typeof value.lat==='function'?value.lat():value.lat),lng:Number(typeof value.lng==='function'?value.lng():value.lng)};

 /** Google-style info card: name, address and action buttons. Built with DOM nodes, never HTML strings. */
 function card(info,lat,lng){
  const root=document.createElement('div');root.className='sm-card';
  const title=document.createElement('strong');title.textContent=info.title||'Location';root.append(title);
  if(info.address){const address=document.createElement('p');address.textContent=info.address;root.append(address);}
  const actions=document.createElement('div');actions.className='sm-card-actions';
  const add=(href,label,className,external)=>{const link=document.createElement('a');link.href=href;link.textContent=label;link.className=className;if(external){link.target='_blank';link.rel='noopener noreferrer';}actions.append(link);};
  add(googleLinks.directions(lat,lng),'Directions','sm-btn sm-btn-primary',true);
  if(info.link)add(info.link.href,info.link.label,'sm-btn',false);
  root.append(actions);
  const open=document.createElement('a');open.href=googleLinks.search(lat,lng);open.target='_blank';open.rel='noopener noreferrer';open.className='sm-open';open.textContent='Open in Google Maps ↗';root.append(open);
  return root;
 }

 function leafletEngine(element,spec){
  if(!window.L){element.textContent='Map could not load. Use the address or directions link.';return null;}
  element.classList.add('sm-map');
  const map=L.map(element,{zoomControl:false,scrollWheelZoom:true,attributionControl:true}).setView(spec.center,spec.zoom||14);
  // Keyless Esri basemaps: street map (light), dark gray canvas with labels (dark), imagery with labels (satellite).
  const esri=service=>L.tileLayer(`https://server.arcgisonline.com/ArcGIS/rest/services/${service}/MapServer/tile/{z}/{y}/{x}`,{maxZoom:19,attribution:'Tiles &copy; <a href="https://www.esri.com">Esri</a> &mdash; Esri, HERE, Garmin, &copy; OpenStreetMap contributors, and the GIS user community'});
  const lightRoad=esri('World_Street_Map'),darkRoad=L.layerGroup([esri('Canvas/World_Dark_Gray_Base'),esri('Canvas/World_Dark_Gray_Reference')]);
  const satellite=L.layerGroup([esri('World_Imagery'),esri('Reference/World_Transportation'),esri('Reference/World_Boundaries_and_Places')]);
  let mode='map',road=dark()?darkRoad:lightRoad;road.addTo(map);
  new MutationObserver(()=>{const next=dark()?darkRoad:lightRoad;if(next===road)return;const showing=map.hasLayer(road);map.removeLayer(road);road=next;if(showing)road.addTo(map);}).observe(document.documentElement,{attributes:true,attributeFilter:['data-theme']});

  const control=(position,build)=>{const Control=L.Control.extend({onAdd(){const box=L.DomUtil.create('div','sm-control');L.DomEvent.disableClickPropagation(box);L.DomEvent.disableScrollPropagation(box);build(box);return box;}});new Control({position}).addTo(map);};
  control('topleft',box=>{box.classList.add('sm-type');for(const [value,label] of [['map','Map'],['satellite','Satellite']]){const button=L.DomUtil.create('button','',box);button.type='button';button.textContent=label;button.setAttribute('aria-pressed',String(value===mode));button.onclick=()=>{if(mode===value)return;mode=value;if(value==='map'){map.removeLayer(satellite);road.addTo(map);}else{map.removeLayer(road);satellite.addTo(map);}box.querySelectorAll('button').forEach(other=>other.setAttribute('aria-pressed',String(other===button)));};}});
  if(document.fullscreenEnabled)control('topright',box=>{const button=L.DomUtil.create('button','sm-icon sm-fullscreen',box);button.type='button';button.setAttribute('aria-label','Toggle full screen');button.title='Full screen';button.onclick=()=>document.fullscreenElement?document.exitFullscreen():element.requestFullscreen?.();});
  document.addEventListener('fullscreenchange',()=>setTimeout(()=>map.invalidateSize(),150));
  let you=null;
  control('bottomright',box=>{const button=L.DomUtil.create('button','sm-icon sm-locate',box);button.type='button';button.setAttribute('aria-label','Show your location');button.title='Your location';button.onclick=()=>{if(!navigator.geolocation)return;button.classList.add('is-busy');navigator.geolocation.getCurrentPosition(position=>{button.classList.remove('is-busy');const here=[position.coords.latitude,position.coords.longitude];if(!you)you=L.marker(here,{icon:L.divIcon({className:'sm-you',html:'<span></span>',iconSize:[22,22],iconAnchor:[11,11]}),keyboard:false,interactive:false}).addTo(map);else you.setLatLng(here);map.setView(here,Math.max(map.getZoom(),15));},()=>button.classList.remove('is-busy'),{timeout:10000,maximumAge:60000});};});
  L.control.zoom({position:'bottomright'}).addTo(map);

  // Google-style "cooperative" scrolling: plain scrolling moves the page, Ctrl/⌘ + scroll (or a trackpad pinch) zooms the map.
  const hint=document.createElement('div');hint.className='sm-hint';hint.textContent=/Mac|iPhone|iPad/.test(navigator.platform)?'Use ⌘ + scroll to zoom the map':'Use Ctrl + scroll to zoom the map';element.append(hint);let hintTimer;
  element.addEventListener('wheel',event=>{if(event.ctrlKey||event.metaKey)return;event.stopPropagation();hint.classList.add('is-visible');clearTimeout(hintTimer);hintTimer=setTimeout(()=>hint.classList.remove('is-visible'),1300);},{capture:true});

  const pinIcon=extra=>L.divIcon({className:'sm-pin'+(extra?' '+extra:''),html:'<svg viewBox="0 0 27 43" width="27" height="43" aria-hidden="true"><path d="M13.5 1C6.6 1 1 6.6 1 13.4c0 9.2 12.5 28.1 12.5 28.1S26 22.6 26 13.4C26 6.6 20.4 1 13.5 1z" fill="#EA4335" stroke="#B31412" stroke-width="1.2"/><circle cx="13.5" cy="13.5" r="4.6" fill="#B31412"/></svg>',iconSize:[27,43],iconAnchor:[13.5,42],popupAnchor:[0,-38]});
  const popupOptions={className:'sm-popup',maxWidth:290,minWidth:220,autoPanPadding:[30,30]};
  const markers=(spec.pins||[]).map(pin=>{const marker=L.marker([pin.lat,pin.lng],{icon:pinIcon(),title:pin.title,riseOnHover:true}).addTo(map);marker.bindPopup(()=>card(pin.card||{title:pin.title},pin.lat,pin.lng),popupOptions);return marker;});
  if(spec.fitPins&&markers.length>1)map.fitBounds(markers.map(marker=>marker.getLatLng()),{padding:[72,72],maxZoom:15});
  else if(spec.fitPins&&markers.length===1)map.setView(markers[0].getLatLng(),15);
  if(spec.openFirst&&markers[0])setTimeout(()=>markers[0].openPopup(),350);

  let pickerMarker=null;
  if(spec.picker){
   const place=(value,notify)=>{const latlng=L.latLng(value.lat,value.lng);if(!pickerMarker){pickerMarker=L.marker(latlng,{icon:pinIcon('is-picker'),draggable:true,title:spec.picker.title||'Selected location',autoPan:true}).addTo(map);pickerMarker.on('dragend',()=>spec.picker.onPick(point(pickerMarker.getLatLng())));}else pickerMarker.setLatLng(latlng);if(notify)spec.picker.onPick(point(latlng));};
   const initial=spec.picker.initial?.();if(initial)place(initial,false);
   map.on('click',event=>place(point(event.latlng),true));
   return {setPin(value){place(value,false);map.panTo([value.lat,value.lng]);},panTo(value){map.panTo([value.lat,value.lng]);}};
  }
  return {panTo(value){map.panTo([value.lat,value.lng]);}};
 }

 function googleEngine(element,spec){
  const maps=google.maps,center=point(spec.center);
  element.classList.add('sm-map','is-google');
  const options={center,zoom:spec.zoom||14,mapId:config().mapId||'DEMO_MAP_ID',mapTypeControl:true,streetViewControl:false,fullscreenControl:true,gestureHandling:'cooperative',clickableIcons:false};
  if(maps.MapTypeControlStyle)options.mapTypeControlOptions={style:maps.MapTypeControlStyle.HORIZONTAL_BAR,position:maps.ControlPosition.TOP_LEFT};
  if(maps.ColorScheme)options.colorScheme=dark()?maps.ColorScheme.DARK:maps.ColorScheme.LIGHT;
  const map=new maps.Map(element,options),info=new maps.InfoWindow();
  const Marker=maps.marker?.AdvancedMarkerElement;
  const makeMarker=(position,title,extra={})=>Marker?new Marker({map,position,title,...extra}):new maps.Marker({map,position,title,draggable:Boolean(extra.gmpDraggable)});
  const markers=(spec.pins||[]).map(pin=>{const marker=makeMarker({lat:pin.lat,lng:pin.lng},pin.title);marker.addListener('click',()=>{info.setContent(card(pin.card||{title:pin.title},pin.lat,pin.lng));info.open({map,anchor:marker});});return {marker,pin};});
  if(spec.fitPins&&markers.length>1){const bounds=new maps.LatLngBounds();markers.forEach(({pin})=>bounds.extend({lat:pin.lat,lng:pin.lng}));map.fitBounds(bounds,72);maps.event.addListenerOnce(map,'idle',()=>{if(map.getZoom()>15)map.setZoom(15);});}
  else if(spec.fitPins&&markers.length===1){map.setCenter({lat:markers[0].pin.lat,lng:markers[0].pin.lng});map.setZoom(15);}
  if(spec.openFirst&&markers[0])maps.event.addListenerOnce(map,'idle',()=>maps.event.trigger(markers[0].marker,'click'));
  if(spec.picker){
   let picked=null;
   const place=(value,notify)=>{if(!picked){picked=makeMarker(value,spec.picker.title||'Selected location',{gmpDraggable:true});picked.addListener('dragend',()=>spec.picker.onPick(point(picked.position||picked.getPosition())));}else if('position' in picked&&Marker)picked.position=value;else picked.setPosition(value);if(notify)spec.picker.onPick(point(value));};
   const initial=spec.picker.initial?.();if(initial)place(initial,false);
   map.addListener('click',event=>place(point(event.latLng),true));
   return {setPin(value){place(value,false);map.panTo(value);},panTo(value){map.panTo(value);}};
  }
  return {panTo(value){map.panTo(value);}};
 }

 function build(record){record.engine=googleReady()?'google':'leaflet';try{record.api=(record.engine==='google'?googleEngine:leafletEngine)(record.element,record.spec);}catch(error){if(record.engine==='google'){record.engine='leaflet';record.element.replaceChildren();record.api=leafletEngine(record.element,record.spec);}else throw error;}}
 /** Mount a map. spec: {center:[lat,lng], zoom, pins:[{lat,lng,title,card}], fitPins, openFirst, picker:{title, initial:()=>point|null, onPick(point)}} */
 function mount(element,spec){
  const record={element,spec,engine:null,api:null};
  record.remount=()=>{const fresh=element.cloneNode(false);fresh.classList.remove('sm-map','is-google');element.replaceWith(fresh);record.element=fresh;build(record);};
  mounted.push(record);
  state==='loading'?queue.push(()=>build(record)):build(record);
  return {setPin:value=>record.api?.setPin?.(value),panTo:value=>record.api?.panTo?.(value)};
 }
 window.SrokMaps={mount,googleReady,links:googleLinks};
})();
