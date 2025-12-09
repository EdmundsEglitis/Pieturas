<x-app-layout>
<x-slot name="header">
<h2 class="text-xl font-semibold">Selected Bus Stops</h2>
</x-slot>

<div class="p-6 space-y-6">
    <p class="text-gray-700 dark:text-gray-300">
        You have selected <strong>{{ count($stops) }}</strong> bus stop(s).
    </p>

    <div class="relative">
        <div id="map" class="w-full h-[600px] rounded border shadow"></div>

        <div class="p-2 rounded bg-white shadow absolute top-4 right-4 z-[9999] text-sm">
            <h4 class="font-bold mb-1">Speed Limit (Latvia)</h4>
            @php
                $colors = [20=>'red',30=>'pink',50=>'orange',70=>'lime',80=>'yellow',90=>'green',100=>'teal',110=>'blue',120=>'purple'];
            @endphp
            @foreach([20,30,50,70,80,90,100,110,120] as $speed)
                <div>
                    <span class="inline-block w-4 h-4 mr-1" style="background-color: {{ $colors[$speed] }};"></span>{{ $speed }}
                </div>
            @endforeach
        </div>
    </div>

    <div class="p-4 border rounded shadow-sm bg-gray-50 dark:bg-gray-900 text-gray-700 dark:text-gray-200">
        <span id="route-info" class="font-semibold">Loading route info...</span>
    </div>

    <div class="space-y-3">
        <button onclick="toggleDirections()" class="px-4 py-2 text-white rounded shadow hover:bg-orange-600 transition" style="background-color:#ff3e00;">
            Show / Hide Directions
        </button>
        <div id="directions-box" class="hidden max-h-96 overflow-y-auto border rounded shadow-sm bg-white dark:bg-gray-800 p-4 text-gray-700 dark:text-gray-200">
            <ol id="directions-list" class="list-decimal list-inside space-y-1"></ol>
        </div>
    </div>

    <div class="overflow-x-auto border rounded mt-4">
        <table class="w-full border-collapse border table-auto">
            <thead class="bg-gray-200">
                <tr>
                    <th class="border p-2">Drag</th>
                    <th class="border p-2">Name</th>
                    <th class="border p-2">Stop Code</th>
                    <th class="border p-2">Street</th>
                    <th class="border p-2">Municipality</th>
                </tr>
            </thead>
            <tbody id="stops-table-body">
                @foreach($stops as $stop)
                    <tr data-id="{{ $stop->id }}" class="cursor-move">
                        <td class="border p-2 text-center">☰</td>
                        <td class="border p-2">{{ $stop->name }}</td>
                        <td class="border p-2">{{ $stop->stop_code }}</td>
                        <td class="border p-2">{{ $stop->street }}</td>
                        <td class="border p-2">{{ $stop->municipality }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <a href="{{ route('dashboard') }}" class="inline-block px-4 py-2 mt-4 bg-gray-300 text-gray-800 rounded shadow hover:bg-gray-400 transition">
        ← Back to Dashboard
    </a>
</div>

<meta name="csrf-token" content="{{ csrf_token() }}">

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

function toggleDirections() { document.getElementById('directions-box').classList.toggle('hidden'); }
function formatDistance(m) { return m<1000?`${m.toFixed(0)} m`:`${(m/1000).toFixed(2)} km`; }
function formatTime(s){const h=Math.floor(s/3600), m=Math.floor((s%3600)/60); return h>0?`${h} h ${m} min`:`${m} min`; }
function getColorForSpeed(speed){return {20:'red',30:'pink',50:'orange',70:'lime',80:'yellow',90:'green',100:'teal',110:'blue',120:'purple'}[speed] ?? 'gray';}

document.addEventListener('DOMContentLoaded', async ()=> {
    let stops=@json($stops);
    if(!stops.length) return;

    const mapStops=stops.map(s=>L.latLng(s.latitude,s.longitude));
    const map=window.map=L.map('map').fitBounds(mapStops);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
        attribution:'&copy; OpenStreetMap contributors', maxZoom:19
    }).addTo(map);

    let orderedStops=[...stops];
    let markers=[];
    let polyMap=[];

    function canonicalizeSegment(seg) {
        const lat1 = Number(seg.lat1.toFixed ? seg.lat1.toFixed(7) : Number(seg.lat1).toFixed(7));
        const lon1 = Number(seg.lon1.toFixed ? seg.lon1.toFixed(7) : Number(seg.lon1).toFixed(7));
        const lat2 = Number(seg.lat2.toFixed ? seg.lat2.toFixed(7) : Number(seg.lat2).toFixed(7));
        const lon2 = Number(seg.lon2.toFixed ? seg.lon2.toFixed(7) : Number(seg.lon2).toFixed(7));
        if (lat1 > lat2 || (lat1 === lat2 && lon1 > lon2)) {
            return {lat1:lat2, lon1:lon2, lat2:lat1, lon2:lon2};
        }
        return {lat1, lon1, lat2, lon2};
    }

    async function drawRoute() {
        map.eachLayer(l=>{ if (l instanceof L.Polyline) map.removeLayer(l); });
        markers.forEach(m=>map.removeLayer(m));
        markers=[]; polyMap=[];

        const coords=orderedStops.map(s=>[s.longitude,s.latitude]);

        let data;
        try{
            const res=await fetch("https://api.openrouteservice.org/v2/directions/driving-car/geojson",{
                method:"POST",
                headers:{"Authorization":"{{ config('services.openrouteservice.key') }}","Content-Type":"application/json"},
                body:JSON.stringify({coordinates:coords, instructions:true})
            });
            if(!res.ok) throw new Error('ORS failed status '+res.status);
            data=await res.json();
            if(!data.features?.length || !data.features[0].geometry?.coordinates) throw new Error('ORS malformed geometry');
        }catch(err){
            console.error('Routing error', err);
            document.getElementById('route-info').textContent='Routing failed';
            addMarkers();
            return;
        }

        const latlngs=data.features[0].geometry.coordinates.map(c=>[c[1],c[0]]);
        const segments=[];
        for(let i=0;i<latlngs.length-1;i++){
            segments.push({lat1:latlngs[i][0], lon1:latlngs[i][1], lat2:latlngs[i+1][0], lon2:latlngs[i+1][1]});
        }

        segments.forEach(seg=>{
            polyMap.push(L.polyline([[seg.lat1,seg.lon1],[seg.lat2,seg.lon2]],{color:'gray', weight:5, opacity:0.7}).addTo(map));
        });

        // DB batch lookup
        let dbSegments=[];
        try{
            const resp = await fetch('/api/road-segments-batch', {
                method:'POST',
                headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken},
                body: JSON.stringify({segments})
            });
            dbSegments = await resp.json();
        }catch(err){
            console.warn('DB batch lookup error', err);
        }

        // Segments missing DB speed
        const missingSegments = [];
        segments.forEach((seg, idx)=>{
            const matched = dbSegments[idx];
            const maxspeed = matched?.maxspeed ?? null;
            if(maxspeed){
                polyMap[idx].setStyle({color:getColorForSpeed(maxspeed)});
                seg.maxspeed=maxspeed;
            } else missingSegments.push(seg);
        });

        // Overpass only for missing segments
        if(missingSegments.length>0){
            const south=Math.min(...missingSegments.map(s=>s.lat1).concat(missingSegments.map(s=>s.lat2))),
                  north=Math.max(...missingSegments.map(s=>s.lat1).concat(missingSegments.map(s=>s.lat2))),
                  west=Math.min(...missingSegments.map(s=>s.lon1).concat(missingSegments.map(s=>s.lon2))),
                  east=Math.max(...missingSegments.map(s=>s.lon1).concat(missingSegments.map(s=>s.lon2)));
            const query=`[out:json];way["highway"]["maxspeed"](${south},${west},${north},${east});out tags geom;`;
            let ways=[];
            try{
                const resp = await fetch('https://overpass-api.de/api/interpreter?data='+encodeURIComponent(query));
                const text = await resp.text();
                ways = text.startsWith('{') ? JSON.parse(text).elements || [] : [];
            }catch(err){ console.warn('Overpass error', err); ways=[]; }

            const toSave=[];
            missingSegments.forEach(seg=>{
                let nearest=null, minDist=Infinity;
                const midLat=(seg.lat1+seg.lat2)/2, midLon=(seg.lon1+seg.lon2)/2;
                ways.forEach(w=>{
                    if(!w.geometry||!w.tags) return;
                    for(let j=0;j<w.geometry.length-1;j++){
                        const pt1=w.geometry[j], pt2=w.geometry[j+1];
                        const latMid=(pt1.lat+pt2.lat)/2, lonMid=(pt1.lon+pt2.lon)/2;
                        const dist = map.distance([midLat,midLon],[latMid,lonMid]);
                        if(dist<minDist){ minDist=dist; nearest=w; }
                    }
                });
                if(nearest && nearest.tags && nearest.tags.maxspeed){
                    const parsedSpeed = parseInt(nearest.tags.maxspeed) || null;
                    if(parsedSpeed){
                        seg.maxspeed=parsedSpeed;
                        seg.mid_lat=Number(((seg.lat1+seg.lat2)/2).toFixed(7));
                        seg.mid_lon=Number(((seg.lat1+seg.lat2)/2).toFixed(7));
                        toSave.push(seg);
                    }
                }
            });

            if(toSave.length>0){
                try{
                    const resp=await fetch('/api/save-road-segment-batch',{
                        method:'POST',
                        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken},
                        body:JSON.stringify({segments:toSave})
                    });
                    if(!resp.ok) console.warn('Save batch returned non-ok', resp.status);
                }catch(err){ console.warn('Save batch error', err); }

                toSave.forEach(seg=>{
                    const idx=segments.findIndex(s=>{
                        const a=canonicalizeSegment(s), b=canonicalizeSegment(seg);
                        return a.lat1===b.lat1 && a.lon1===b.lon1 && a.lat2===b.lat2 && a.lon2===b.lon2;
                    });
                    if(idx>=0) polyMap[idx].setStyle({color:getColorForSpeed(seg.maxspeed)});
                });
            }
        }

        addMarkers();

        const summary = data.features[0].properties?.summary;
        if(summary) document.getElementById('route-info').textContent=`Route: ${formatDistance(summary.distance)}, ${formatTime(summary.duration)}`;
        else document.getElementById('route-info').textContent=`Route loaded`;

        const steps = data.features[0].properties?.segments?.[0]?.steps || [];
        const dirList = document.getElementById('directions-list');
        dirList.innerHTML='';
        steps.forEach(step=>{
            const li=document.createElement('li');
            li.textContent=`${step.instruction} — ${formatDistance(step.distance)}, ${formatTime(step.duration)}`;
            dirList.appendChild(li);
        });
    }

    function addMarkers(){
        orderedStops.forEach(stop=>{
            const m=L.marker([stop.latitude, stop.longitude],{draggable:true})
                .bindPopup(stop.name)
                .addTo(map)
                .on('dragend', e=>{
                    stop.latitude=e.target.getLatLng().lat;
                    stop.longitude=e.target.getLatLng().lng;
                    drawRoute();
                });
            markers.push(m);
        });
    }

    drawRoute();

    Sortable.create(document.getElementById('stops-table-body'),{
        animation:150,
        onEnd:function(){
            orderedStops=Array.from(document.querySelectorAll('#stops-table-body tr')).map(tr=>{
                const id=parseInt(tr.dataset.id);
                return stops.find(s=>s.id===id);
            });
            drawRoute();
        }
    });
});
</script>

</x-app-layout>
