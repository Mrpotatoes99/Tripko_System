/* Location Picker Logic */
// Use absolute (from web root /tripko-system) to avoid relative path issues from nested directories
const apiMarkersUrl = '/tripko-system/tripko-backend/api/map/markers.php'; // reused (not currently called directly here)
const savePointUrl = '/tripko-system/tripko-backend/api/map/save_point.php';

importMap();

let map, dragMarker = null;
let allItems = [];
let selected = null;

const itemList = document.getElementById('itemList');
const filterType = document.getElementById('filterType');
const searchBox = document.getElementById('searchBox');
const selectedName = document.getElementById('selectedName');
const latInput = document.getElementById('latInput');
const lngInput = document.getElementById('lngInput');
const saveBtn = document.getElementById('saveBtn');
const locateBtn = document.getElementById('locateBtn');
const statusLine = document.getElementById('statusLine');

filterType.addEventListener('change', renderList);
searchBox.addEventListener('input', ()=>{renderList();});
latInput.addEventListener('input', onCoordManualEdit);
lngInput.addEventListener('input', onCoordManualEdit);
saveBtn.addEventListener('click', saveLocation);
locateBtn.addEventListener('click', useMyLocation);

async function importMap(){
  map = new maplibregl.Map({
    container:'map',
    style:{
      version:8,
      sources:{
        'osm':{type:'raster',tiles:[
          'https://a.tile.openstreetmap.org/{z}/{x}/{y}.png',
          'https://b.tile.openstreetmap.org/{z}/{x}/{y}.png',
          'https://c.tile.openstreetmap.org/{z}/{x}/{y}.png'
        ],tileSize:256,attribution:'© OpenStreetMap contributors'}
      },
      layers:[{id:'base',type:'raster',source:'osm'}]
    },
    center:[120.3333,15.9000],
    zoom:9
  });
  map.addControl(new maplibregl.NavigationControl(),'top-left');
  await loadUnmapped();
}

async function loadUnmapped(){
  // For now: fetch raw lists from custom endpoint (to be created) else reuse existing tables by building a small combined payload
  try {
  const res = await fetch('/tripko-system/tripko-backend/api/map/unmapped_list.php');
    const data = await res.json();
    allItems = data.items || []; // [{id, name, type, has_coords, lat, lng, accuracy}]
    renderList();
  } catch(e){
    console.error(e);
  }
}

function renderList(){
  const typeFilter = filterType.value;
  const q = searchBox.value.toLowerCase();
  itemList.innerHTML='';
  allItems
    .filter(it=> typeFilter==='all' || it.type===typeFilter)
    .filter(it=> !q || it.name.toLowerCase().includes(q))
    .forEach(it=>{
      const li = document.createElement('li');
      li.className='item';
  if(!it.has_coords) li.classList.add('unmapped'); else if(it.accuracy && it.accuracy!=='exact') li.classList.add('approx');
      li.innerHTML = `<span>${it.name}</span>` +
        `<span class="badge ${!it.has_coords?'unmapped':(it.accuracy!=='exact'?'approx':'')}">`+
        (!it.has_coords?'none':it.accuracy)+`</span>`;
      li.addEventListener('click',()=>selectItem(it));
      itemList.appendChild(li);
    });
}

function selectItem(it){
  selected = it;
  selectedName.textContent = `${it.name} (${it.type})`;
  if(dragMarker){ dragMarker.remove(); dragMarker=null; }
  const start = it.has_coords ? [it.lng,it.lat] : [120.3333,15.9000];
  dragMarker = new maplibregl.Marker({draggable:true,color: it.has_coords?'#2563eb':'#dc2626'})
    .setLngLat(start)
    .addTo(map);
  map.flyTo({center:start,zoom: it.has_coords?13:11});
  latInput.value = it.lat || '';
  lngInput.value = it.lng || '';
  dragMarker.on('dragend',()=>{
    const ll = dragMarker.getLngLat();
    latInput.value = ll.lat.toFixed(6);
    lngInput.value = ll.lng.toFixed(6);
    validateCoords();
  });
  validateCoords();
}

function onCoordManualEdit(){
  if(!selected || !dragMarker) return;
  const lat = parseFloat(latInput.value); const lng = parseFloat(lngInput.value);
  if(isFinite(lat) && isFinite(lng)){
    dragMarker.setLngLat([lng,lat]);
  }
  validateCoords();
}

function validateCoords(){
  const lat = parseFloat(latInput.value); const lng = parseFloat(lngInput.value);
  const ok = isFinite(lat) && isFinite(lng) && lat<=90 && lat>=-90 && lng<=180 && lng>=-180;
  saveBtn.disabled = !(ok && selected);
  statusLine.textContent = ok? '' : 'Invalid coordinates';
}

async function saveLocation(){
  if(!selected) return;
  const lat = parseFloat(latInput.value); const lng = parseFloat(lngInput.value);
  if(!isFinite(lat) || !isFinite(lng)) return;
  const payload = { entity_type: selected.type, entity_id: selected.id, latitude: lat, longitude: lng, accuracy: 'exact' };
  saveBtn.disabled = true;
  statusLine.textContent = 'Saving...';
  try {
    const res = await fetch(savePointUrl,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
    const j = await res.json();
    if(!res.ok || j.error){ throw new Error(j.message||'Save failed'); }
    statusLine.textContent = 'Saved';
    // update local item
    selected.lat = lat; selected.lng = lng; selected.has_coords = true; selected.accuracy='exact';
    renderList();
  } catch(e){
    console.error(e); statusLine.textContent = 'Error: '+e.message;
  } finally {
    saveBtn.disabled = false;
  }
}

function useMyLocation(){
  if(!navigator.geolocation) return;
  navigator.geolocation.getCurrentPosition(pos=>{
    const {latitude, longitude} = pos.coords;
    latInput.value = latitude.toFixed(6);
    lngInput.value = longitude.toFixed(6);
    if(dragMarker){ dragMarker.setLngLat([longitude, latitude]); }
    validateCoords();
  });
}
