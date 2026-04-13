// Fotos Rapidas para finalizacion de limpieza
var _fotoAreas2=[{key:'cocina',icon:'\uD83C\uDF73',name:'Cocina'},{key:'salon',icon:'\uD83D\uDECB\uFE0F',name:'Salon'},{key:'comedor',icon:'\uD83C\uDF7D\uFE0F',name:'Comedor'},{key:'dormitorio',icon:'\uD83D\uDECF\uFE0F',name:'Dormitorio'},{key:'bano',icon:'\uD83D\uDEBF',name:'Bano'}];
var _fotoIdx2=0,_limpId2=0;

function _initFotosRapidas(limpiezaId){_limpId2=limpiezaId||0;}

// Auto-detect limpieza ID from forms
document.addEventListener('DOMContentLoaded',function(){
    // Try formPrincipalLimpieza (checklist-tarea view)
    var f=document.getElementById('formPrincipalLimpieza');
    if(f&&f.dataset.limpiezaId){_limpId2=parseInt(f.dataset.limpiezaId)||0;return;}
    // Try formFinalizar (edit-tarea view) - extract ID from form action URL
    var f2=document.getElementById('formFinalizar');
    if(f2&&f2.action){
        var m=f2.action.match(/gestion-finalizar\/(\d+)/);
        if(m&&m[1]){_limpId2=parseInt(m[1])||0;return;}
    }
});

function _mostrarFotosYFinalizar(){
    if(!_limpId2){_enviarFinalizacionReal();return;}
    // Create overlay if not exists
    if(!document.getElementById('fotoOverlay')){_crearOverlay();}
    _fotoIdx2=0;
    document.getElementById('fotoOverlay').style.display='block';
    _actualizarFoto2();
}

function _crearOverlay(){
    var d=document.createElement('div');
    d.id='fotoOverlay';
    d.style.cssText='display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:#000;z-index:9999;color:#fff;';
    d.innerHTML='<div style="text-align:center;padding-top:30px;height:100%;display:flex;flex-direction:column;align-items:center;">'
        +'<h2 id="fotoNombre" style="font-size:28px;margin-bottom:8px;font-weight:700;"></h2>'
        +'<p id="fotoContador" style="font-size:14px;color:#aaa;margin-bottom:10px;"></p>'
        +'<div id="fotoDots" style="margin:10px 0;font-size:20px;"><span class="dot">\u25CF</span> <span class="dot">\u25CF</span> <span class="dot">\u25CF</span> <span class="dot">\u25CF</span> <span class="dot">\u25CF</span></div>'
        +'<div style="flex:1;display:flex;align-items:center;justify-content:center;width:100%;padding:10px;">'
        +'<img id="fotoPreviewImg" style="max-width:90%;max-height:50vh;border-radius:12px;display:none;">'
        +'<div id="fotoIcono" style="font-size:100px;"></div></div>'
        +'<input type="file" id="fotoInput" accept="image/*" capture="environment" style="display:none;">'
        +'<button id="fotoCaptureBtn" onclick="document.getElementById(\'fotoInput\').click()" style="width:80px;height:80px;border-radius:50%;background:#0891b2;border:4px solid #fff;color:#fff;font-size:30px;margin:10px 0;">\uD83D\uDCF8</button>'
        +'<button onclick="_omitirFoto()" style="background:none;border:none;color:#666;font-size:13px;text-decoration:underline;margin-bottom:30px;">Omitir esta foto</button>'
        +'</div>';
    document.body.appendChild(d);
    // Attach file input listener
    document.getElementById('fotoInput').addEventListener('change',function(e){
        var file=e.target.files[0];if(!file)return;
        var prev=URL.createObjectURL(file);
        document.getElementById('fotoPreviewImg').src=prev;
        document.getElementById('fotoPreviewImg').style.display='block';
        document.getElementById('fotoIcono').style.display='none';
        document.getElementById('fotoCaptureBtn').style.display='none';
        var csrf=document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        _comprimirImg2(file,1200,0.7).then(function(blob){
            var fd=new FormData();fd.append('image',blob,'photo.jpg');
            fd.append('area',_fotoAreas2[_fotoIdx2].key);fd.append('_token',csrf);
            fetch('/gestion/limpieza/'+_limpId2+'/foto-rapida',{
                method:'POST',headers:{'X-CSRF-TOKEN':csrf},body:fd
            }).catch(function(err){console.error(err);});
        });
        setTimeout(function(){
            URL.revokeObjectURL(prev);_fotoIdx2++;
            if(_fotoIdx2>=5)_cerrarFotosYFinalizar();
            else{_actualizarFoto2();document.getElementById('fotoInput').value='';}
        },1000);
    });
}

function _actualizarFoto2(){
    var a=_fotoAreas2[_fotoIdx2];
    document.getElementById('fotoNombre').textContent=a.name;
    document.getElementById('fotoIcono').textContent=a.icon;
    document.getElementById('fotoContador').textContent='Foto '+(_fotoIdx2+1)+' de 5';
    document.getElementById('fotoPreviewImg').style.display='none';
    document.getElementById('fotoIcono').style.display='block';
    document.getElementById('fotoCaptureBtn').style.display='inline-block';
    var d=document.querySelectorAll('#fotoDots .dot');
    for(var i=0;i<d.length;i++)d[i].style.color=i<=_fotoIdx2?'#0891b2':'#444';
}

function _omitirFoto(){
    _fotoIdx2++;
    if(_fotoIdx2>=5)_cerrarFotosYFinalizar();
    else{_actualizarFoto2();document.getElementById('fotoInput').value='';}
}

function _cerrarFotosYFinalizar(){
    document.getElementById('fotoOverlay').style.display='none';
    _enviarFinalizacionReal();
}

function _enviarFinalizacionReal(){
    var ol=document.getElementById('loadingOverlay');
    if(ol)ol.style.display='flex';

    // Try checklist-tarea form (AJAX)
    var f=document.getElementById('formPrincipalLimpieza');
    if(f){
        var fd=new FormData(f);
        fd.append('accion','finalizar');
        fd.set('consentimiento_finalizacion','true');
        fd.set('motivo_consentimiento','Finalizado con fotos');
        fetch(f.action,{
            method:'POST',body:fd,
            headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').getAttribute('content')}
        }).then(function(r){return r.json();}).then(function(d){
            if(d.success)window.location.href='/limpiadora/dashboard';
            else{if(ol)ol.style.display='none';alert(d.message||'Error');}
        }).catch(function(){window.location.href='/limpiadora/dashboard';});
        return;
    }

    // Try edit-tarea form (form submit)
    var f2=document.getElementById('formFinalizar');
    if(f2){
        var chk=document.getElementById('consentimientoFinalizarHidden');
        if(chk)chk.value='true';
        var mot=document.getElementById('motivoConsentimientoHidden');
        if(mot)mot.value='Finalizado con fotos';
        f2.submit();
        return;
    }

    // Fallback
    window.location.href='/limpiadora/dashboard';
}

function _comprimirImg2(file,mW,q){
    return new Promise(function(res){
        var r=new FileReader();r.onload=function(e){
            var img=new Image();img.onload=function(){
                var c=document.createElement('canvas'),w=img.width,h=img.height;
                if(w>mW){h=Math.round(h*mW/w);w=mW;}c.width=w;c.height=h;
                c.getContext('2d').drawImage(img,0,0,w,h);c.toBlob(res,'image/jpeg',q);
            };img.src=e.target.result;
        };r.readAsDataURL(file);
    });
}
