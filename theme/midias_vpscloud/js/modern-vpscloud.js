(function(){'use strict';const q=(s,p=document)=>p.querySelector(s),qa=(s,p=document)=>[...p.querySelectorAll(s)];q('#year').textContent=new Date().getFullYear();const menu=q('.menu-btn'),nav=q('.nav nav');menu.addEventListener('click',()=>{const open=nav.classList.toggle('open');menu.setAttribute('aria-expanded',open)});qa('.nav nav a').forEach(a=>a.addEventListener('click',()=>nav.classList.remove('open')));
const dateFormatter=new Intl.DateTimeFormat('pt-BR',{day:'2-digit',month:'2-digit',year:'numeric'}),timeFormatter=new Intl.DateTimeFormat('pt-BR',{hour:'2-digit',minute:'2-digit',second:'2-digit'});function updateClock(){const now=new Date(),date=q('#live-date'),clock=q('#live-clock');if(date)date.textContent=dateFormatter.format(now);if(clock)clock.textContent=timeFormatter.format(now)}updateClock();setInterval(updateClock,1000);fetch('abgs-visitor.php',{cache:'no-store',credentials:'same-origin'}).then(r=>{if(!r.ok)throw new Error('visitor');return r.json()}).then(v=>{q('#visitor-ip').textContent=v.ip||'não identificado';q('#visitor-count').textContent=Number(v.count||0).toLocaleString('pt-BR')}).catch(()=>{q('#visitor-ip').textContent='indisponível';q('#visitor-count').textContent='—'});
const slides=qa('.hero-bg'),dots=q('.hero-dots');let active=0;slides.forEach((_,i)=>{const b=document.createElement('button');b.className=i===0?'active':'';b.onclick=()=>show(i);dots.appendChild(b)});function show(i){slides[active].classList.remove('active');dots.children[active].classList.remove('active');active=i;slides[active].classList.add('active');dots.children[active].classList.add('active')}if(slides.length)setInterval(()=>show((active+1)%slides.length),6000);
let planVisualMode='dynamic';
let whatsappNumber='',cadastroRoute='cadastro-sistema.php';fetch('abgs-data.php',{cache:'no-store'}).then(r=>{if(!r.ok)throw new Error('dados');return r.json()}).then(data=>{if(data.ok===false||!Array.isArray(data.planos))throw new Error('dados');const allowedVisuals=['dynamic','network','fiber','rural','combo','service','cloud','api','support','license','regulatory'];const requestedVisual=(data.config||{}).plan_visual_mode;planVisualMode=allowedVisuals.includes(requestedVisual)?requestedVisual:'dynamic';const e=data.empresa||{},mode=(data.config||{}).cadastro_modo;cadastroRoute=mode==='whatsapp'?'cadastro-whatsapp.hhvm':'cadastro-sistema.php';qa('[data-name]').forEach(x=>x.textContent=e.nome||'Provedor de Internet');qa('[data-phone]').forEach(x=>x.textContent=e.fone||e.celular||'Fale conosco');qa('[data-email]').forEach(x=>x.textContent=e.email||'');qa('[data-phone-link]').forEach(x=>x.href='tel:'+(e.fone||e.celular||'').replace(/\D/g,''));qa('[data-email-link]').forEach(x=>x.href='mailto:'+(e.email||''));whatsappNumber=(e.whatsapp||e.celular||e.fone||'').replace(/\D/g,'').replace(/^55/,'');qa('[data-whatsapp]').forEach(x=>x.href=whatsappNumber?'https://wa.me/55'+whatsappNumber:'#atendimento');document.title=e.nome||document.title;renderPlans(data.planos||[]);updatePlanNav()}).catch(()=>(q('#plans')||q('#all-plans')).innerHTML='<div class="loading">Não foi possível carregar os planos. Tente atualizar a página ou entre em contato.</div>');
window.vpscloudPlanVisual=planVisual;
function planVisual(name,mode=planVisualMode){
  const title=String(name).normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().replace(/[_-]+/g,' ');
  const detected=/\b(cft|crt|anatel|crea|scm|sici|mosaico|outorga|homologacao|regularizacao|responsabilidade tecnica)\b/.test(title)?'regulatory':
    /\b(licenca|licencas|licenciamento|license|licence|ativacao|chave de ativacao)\b/.test(title)?'license':
    /\b(suporte|support|helpdesk|help desk|assistencia|manutencao|monitoramento|noc)\b/.test(title)?'support':
    /\b(servico|servicos|consultoria|consulting|assessoria|instalacao|configuracao|treinamento|visita tecnica|marketing)\b/.test(title)?'service':
    /\b(combo|pacote|bundle|duo|triple play|quad play)\b/.test(title)?'combo':
    /\b(cloud|vps|vds|ssd|ram|cpu|vcpu|servidor|servidores|hospedagem|hosting|datacenter|data center|backup|nuvem)\b/.test(title)?'cloud':
    /\b(api|wpp|whatsapp|integracao|integracoes|webhook|automacao|bot|chatbot)\b/.test(title)?'api':
    /\b(rural|fazenda|sitio|chacara|campo|agro|agricola|zona rural)\b/.test(title)?'rural':
    /\b(fibra|fiber|ftth|fttx|gpon|epon|fibra optica)\b/.test(title)?'fiber':
    /\b(internet|banda larga|broadband|wifi|wi fi|wireless|radio|link dedicado|link empresarial|residencial|mega|megas|giga|gigas|mbps|gbps)\b|\d\s*(mega|giga|mb|gb)|^\d+$/.test(title)?'network':'service';
  const type=['network','fiber','rural','combo','service','cloud','api','support','license','regulatory'].includes(mode)?mode:detected;
  const art={
    network:'<path class="signal" d="M49 43Q96 2 143 43M65 58Q96 31 127 58M81 72Q96 59 111 72"/><circle class="light" cx="96" cy="83" r="4"/><path d="M51 95V75M141 95V75"/><rect class="device" x="34" y="95" width="124" height="32" rx="10"/><path d="M47 127v6M145 127v6"/><circle class="light" cx="51" cy="111" r="3"/><circle class="light" cx="64" cy="111" r="3"/><path d="M119 110h24"/>',
    cloud:'<path class="signal" d="M61 37H49a14 14 0 0 1-1-28 25 25 0 0 1 46 0 14 14 0 0 1 2 28H84"/><path class="connector" d="M72 37v12h24v12"/><rect class="device" x="38" y="61" width="118" height="30" rx="8"/><rect class="device" x="38" y="101" width="118" height="30" rx="8"/><circle class="light" cx="53" cy="76" r="3"/><circle class="light" cx="53" cy="116" r="3"/><path d="M73 76h26M116 72h23M116 80h23M73 116h26M116 112h23M116 120h23"/>',
    api:'<path class="connector" d="M55 47h37v50h37M92 47h37M55 111h37V97"/><rect class="device" x="13" y="23" width="44" height="44" rx="11"/><rect class="device" x="130" y="23" width="44" height="44" rx="11"/><rect class="device" x="13" y="89" width="44" height="44" rx="11"/><rect class="device" x="130" y="77" width="44" height="44" rx="11"/><path class="signal" d="m29 36-7 9 7 9m12-18 7 9-7 9M143 45l6 6 12-13M26 104h18M26 116h11M143 99h18m-9-9v18"/><circle class="light" cx="92" cy="73" r="5"/>'
  };
  art.service='<path class="signal" d="M49 81V65a47 47 0 0 1 94 0v16"/><rect class="device" x="37" y="71" width="25" height="40" rx="10"/><rect class="device" x="130" y="71" width="25" height="40" rx="10"/><path d="M142 112v6a12 12 0 0 1-12 12h-19"/><rect class="device" x="84" y="122" width="28" height="14" rx="7"/><path class="connector" d="M78 73h36M78 85h23"/>';
  art.combo='<rect class="device" x="18" y="22" width="125" height="77" rx="9"/><path d="M63 113h39M83 99v14"/><path class="signal" d="m69 43 27 18-27 18Z"/><rect class="device" x="127" y="64" width="45" height="69" rx="8"/><path d="M139 74h21M143 123h14"/><path class="signal" d="M27 121q26-23 52 0m-42 9q16-14 32 0"/>';
  art.rural='<path class="signal" d="M17 122h158M30 120V85l29-24 29 24v35M47 121V99h23v22"/><path class="device" d="m111 121 22-77 23 77Z"/><path d="m116 101 33-22m-31 0 33 22m-24-40h13"/><circle class="light" cx="133" cy="39" r="4"/><path class="signal" d="M119 27a18 18 0 0 0 0 25m28-25a18 18 0 0 1 0 25M109 17a32 32 0 0 0 0 45m48-45a32 32 0 0 1 0 45"/>';
  art.fiber='<rect class="device" x="23" y="80" width="145" height="44" rx="12"/><path d="M41 124v8m109-8v8M55 80V57m80 23V57"/><circle class="light" cx="41" cy="102" r="4"/><circle class="light" cx="57" cy="102" r="4"/><path class="signal" d="M83 80V45q0-20 22-20h48M94 80V52q0-15 18-15h41M105 80V60q0-11 14-11h34"/><path d="M122 100h27"/><circle class="light" cx="158" cy="25" r="4"/><circle class="light" cx="158" cy="37" r="4"/><circle class="light" cx="158" cy="49" r="4"/>';
  art.support=art.service;
  art.service='<rect class="device" x="25" y="50" width="142" height="80" rx="12"/><path class="signal" d="M66 50V35h59v15M25 78q70 29 142 0"/><rect class="device" x="82" y="77" width="28" height="20" rx="4"/>';
  art.license='<path class="device" d="M36 15h75l24 24v94H36Z"/><path d="M110 15v27h25M52 56h48M52 70h37"/><circle class="signal" cx="132" cy="93" r="25"/><path class="signal" d="m120 93 9 9 17-20M119 117l-5 21 18-8 17 8-5-21"/>';
  art.regulatory='<path class="device" d="M28 17h77l19 20v96H28Z"/><path d="M43 43h48M43 58h48M43 73h30"/><path class="signal" d="m115 131 19-66 20 66M121 109h26M129 86h11"/><circle class="light" cx="134" cy="56" r="4"/><path class="signal" d="M122 43a17 17 0 0 0 0 26m24-26a17 17 0 0 1 0 26M112 33a31 31 0 0 0 0 46m44-46a31 31 0 0 1 0 46"/>';
  const labels={support:['Atendimento técnico','Suporte','Ajuda para sua operação'],license:['Software e acesso','Licenças','Recursos para seu negócio'],regulatory:['Documentação técnica','Regularização','Apoio para sua operação'],combo:['Mais possibilidades','Combo','Conexões e serviços juntos'],rural:['Conectividade no campo','Internet Rural','Conexão perto de você'],fiber:['Tecnologia em fibra','Fibra','Velocidade para sua rotina'],service:['Atendimento','Serviços','Soluções para você'],network:['Conectividade','Internet','Seu dia mais conectado'],cloud:['Infraestrutura','Cloud','Espaço para suas ideias'],api:['Tecnologia','Integrações','Seus sistemas conectados']};
  const label=labels[type];
  return `<div class="plan-visual ${type}"><div class="plan-heading"><small>${label[0]}</small><b>${label[1]}</b><span class="plan-caption">${label[2]}</span></div><svg class="plan-art" viewBox="0 0 192 144" aria-hidden="true" focusable="false">${art[type]}</svg></div>`;
}
function renderPlans(plans){const box=q('#plans')||q('#all-plans');if(!plans.length){box.innerHTML='<div class="loading">Consulte nossos planos pelo atendimento.</div>';return}box.innerHTML=plans.map((p,i)=>{const lines=(p.descricao||'Internet estável\nAtendimento próximo').split(/\r?\n/).filter(Boolean).slice(0,4);const price=Number(String(p.valor).replace(',','.')).toLocaleString('pt-BR',{minimumFractionDigits:2});return `<article class="plan">${planVisual(p.nome,i)}<div class="plan-body"><h3 title="${esc(p.nome)}">${esc(p.nome)}</h3><div class="price"><small>R$</small><b>${price}</b><small>/mês</small></div><ul>${lines.map(x=>`<li>${esc(x.replace(/^[-•]\s*/,''))}</li>`).join('')}</ul><a class="btn primary" href="${cadastroRoute}?plano=${encodeURIComponent(p.nome)}">Selecionar plano</a></div></article>`}).join('');if(q('#all-plans'))q('#all-plans').innerHTML=box.innerHTML}
function esc(v){return String(v||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}
const plansRail=q('#plans'),planControls=q('.plan-controls'),previousPlan=q('.slider-arrow.prev'),nextPlan=q('.slider-arrow.next'),planRange=q('#plan-range');
let selectedPlan=0,planAnimating=false,pendingPlanMoves=0;
function planOffset(index,selected,count){let offset=(index-selected+count)%count;if(offset>count/2)offset-=count;return Math.max(-2,Math.min(2,offset))}
function planPose(offset){
 const mobile=matchMedia('(max-width:820px)').matches;
 return {transform:'perspective(1100px) translateX('+(offset*(mobile?78:94))+'%) translateZ('+(-Math.abs(offset)*100)+'px) rotateY('+(-offset*26)+'deg) scale('+(offset===0?.94:.82)+')',opacity:Math.abs(offset)>1?0:offset===0?1:.78};
}
function updatePlanNav(){
 if(!plansRail||!planControls)return;
 const cards=qa('.plan',plansRail),count=cards.length;planControls.hidden=count<2;const hint=q('.mobile-plan-hint');if(hint)hint.hidden=count<2;if(!count)return;
 selectedPlan=(selectedPlan+count)%count;
 cards.forEach((card,index)=>{
  const offset=planOffset(index,selectedPlan,count),visible=Math.abs(offset)<=1;
  card.dataset.position=offset===0?'current':offset===-1?'left':offset===1?'right':'hidden';
  Object.assign(card.style,planPose(offset),{visibility:visible?'visible':'hidden',zIndex:offset===0?'3':'1'});
  card.inert=!visible;card.setAttribute('aria-hidden',visible?'false':'true');
 });
 planRange.textContent='Plano '+(selectedPlan+1)+' de '+count;
}
async function movePlans(direction){
 if(planAnimating){pendingPlanMoves=direction;return}
 const cards=qa('.plan',plansRail),count=cards.length;if(count<2)return;
 const oldSelected=selectedPlan,newSelected=(selectedPlan+direction+count)%count;
 if(matchMedia('(prefers-reduced-motion: reduce)').matches){selectedPlan=newSelected;updatePlanNav();return}
 planAnimating=true;const animations=[];
 cards.forEach((card,index)=>{
  let from=planOffset(index,oldSelected,count),to=planOffset(index,newSelected,count);
  if(Math.abs(from)>1&&Math.abs(to)>1)return;
  if(Math.abs(to-from)>1){if(Math.abs(to)<=1)from=to+direction;else to=from-direction}
  card.style.visibility='visible';card.style.zIndex=to===0?'3':from===0?'2':'1';
  animations.push(card.animate([planPose(from),planPose(to)],{duration:680,easing:'cubic-bezier(.22,.61,.36,1)',fill:'forwards'}));
 });
 await Promise.allSettled(animations.map(a=>a.finished));
 selectedPlan=newSelected;updatePlanNav();animations.forEach(a=>a.cancel());planAnimating=false;
 if(pendingPlanMoves){const next=pendingPlanMoves;pendingPlanMoves=0;movePlans(next)}
}
window.addEventListener('resize',()=>{if(!planAnimating)updatePlanNav()});
if(nextPlan)nextPlan.onclick=()=>movePlans(1);if(previousPlan)previousPlan.onclick=()=>movePlans(-1);
plansRail?.addEventListener('keydown',e=>{if(e.key==='ArrowLeft'||e.key==='ArrowRight'){e.preventDefault();movePlans(e.key==='ArrowRight'?1:-1)}});
let dragStart=null,suppressPlanClickUntil=0;
plansRail?.addEventListener('click',e=>{
 if(Date.now()<suppressPlanClickUntil){e.preventDefault();e.stopPropagation();return}
 const card=e.target.closest('.plan');if(!card)return;
 const position=card.dataset.position;
 if(position==='left'||position==='right'){e.preventDefault();e.stopPropagation();movePlans(position==='right'?1:-1)}
},true);
plansRail?.addEventListener('pointerdown',e=>{
 if(e.pointerType==='touch'||!e.isPrimary||e.button!==0)return;
 dragStart={id:e.pointerId,x:e.clientX,y:e.clientY};
});
plansRail?.addEventListener('pointermove',e=>{
 if(!dragStart||e.pointerId!==dragStart.id)return;
 const dx=e.clientX-dragStart.x,dy=e.clientY-dragStart.y;
 if(Math.abs(dx)>12&&Math.abs(dx)>Math.abs(dy)){
  suppressPlanClickUntil=Date.now()+500;
  if(!plansRail.hasPointerCapture(e.pointerId))plansRail.setPointerCapture(e.pointerId);
  plansRail.classList.add('is-dragging');
 }
});
plansRail?.addEventListener('pointerup',e=>{
 if(!dragStart||e.pointerId!==dragStart.id)return;
 const dx=e.clientX-dragStart.x,dy=e.clientY-dragStart.y;
 dragStart=null;plansRail.classList.remove('is-dragging');
 if(plansRail.hasPointerCapture(e.pointerId))plansRail.releasePointerCapture(e.pointerId);
 if(Math.abs(dx)>35&&Math.abs(dx)>Math.abs(dy)){suppressPlanClickUntil=Date.now()+500;movePlans(dx<0?1:-1)}
});
plansRail?.addEventListener('pointercancel',()=>{dragStart=null;plansRail.classList.remove('is-dragging')});
// Lock horizontal touch gestures before Safari starts native scrolling.
let touchDrag=null;
plansRail?.addEventListener('touchstart',e=>{
 if(e.touches.length!==1){touchDrag=null;return;}
 const t=e.touches[0];touchDrag={id:t.identifier,x:t.clientX,y:t.clientY,dx:0,axis:null};
},{passive:true});
plansRail?.addEventListener('touchmove',e=>{
 if(!touchDrag||e.touches.length!==1)return;
 const t=e.touches[0];if(t.identifier!==touchDrag.id)return;
 const dx=t.clientX-touchDrag.x,dy=t.clientY-touchDrag.y;
 if(!touchDrag.axis&&Math.max(Math.abs(dx),Math.abs(dy))>6)touchDrag.axis=Math.abs(dx)>Math.abs(dy)?'x':'y';
 if(touchDrag.axis==='x'){
  if(e.cancelable)e.preventDefault();
  touchDrag.dx=dx;suppressPlanClickUntil=Date.now()+600;
  plansRail.classList.add('is-dragging');
 }
},{passive:false});
plansRail?.addEventListener('touchend',e=>{
 if(!touchDrag)return;
 const gesture=touchDrag;touchDrag=null;plansRail.classList.remove('is-dragging');
 if(gesture.axis==='x'){
  if(e.cancelable)e.preventDefault();
  suppressPlanClickUntil=Date.now()+600;
  if(Math.abs(gesture.dx)>30)movePlans(gesture.dx<0?1:-1);
 }
},{passive:false});
plansRail?.addEventListener('touchcancel',()=>{touchDrag=null;plansRail.classList.remove('is-dragging');});
plansRail?.addEventListener('dragstart',e=>e.preventDefault());
q('#contact-form')?.addEventListener('submit',e=>{e.preventDefault();const f=new FormData(e.currentTarget),parts=[`Olá! Meu nome é ${f.get('nome')}.`,`Assunto: ${f.get('assunto')}.`,`WhatsApp: ${f.get('whatsapp')}.`,f.get('email')?`E-mail: ${f.get('email')}.`:'',f.get('cidade')?`Cidade: ${f.get('cidade')}.`:'',f.get('bairro')?`Bairro/localidade: ${f.get('bairro')}.`:'',f.get('endereco')?`Endereço: ${f.get('endereco')}.`:'',f.get('mensagem')?`Mensagem: ${f.get('mensagem')}`:''].filter(Boolean);if(whatsappNumber)window.open('https://wa.me/55'+whatsappNumber+'?text='+encodeURIComponent(parts.join('\n')),'_blank');else location.href='mailto:?subject='+encodeURIComponent(f.get('assunto'))+'&body='+encodeURIComponent(parts.join('\n'))});
const observer=new IntersectionObserver(entries=>entries.forEach(entry=>{if(entry.isIntersecting){entry.target.classList.add('in-view');observer.unobserve(entry.target)}}),{threshold:.15});qa('.reveal,.feature').forEach(el=>observer.observe(el));

const phoneScenes=qa('.phone-scene'),phoneDots=qa('[data-phone-slide]');
if(phoneScenes.length){
 let phoneIndex=0,phoneVisible=false,phonePaused=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
 let lastPhoneAction=0;
 const wings=qa('.phone-wing');
 const showPhone=(index)=>{phoneIndex=(index+phoneScenes.length)%phoneScenes.length;index=phoneIndex;
 wings.forEach((wing,i)=>{const n=(index+(i===0?-1:1)+phoneScenes.length)%phoneScenes.length;wing.dataset.scene=n;wing.querySelector('img').src=phoneScenes[n].querySelector('img').src;wing.setAttribute('aria-label','Mostrar '+phoneScenes[n].querySelector('strong').textContent)});
phoneScenes.forEach((el,i)=>{el.classList.toggle('active',i===index);el.setAttribute('aria-hidden',String(i!==index))});phoneDots.forEach((el,i)=>{el.classList.toggle('active',i===index);el.setAttribute('aria-pressed',String(i===index))})};
 const selectPhone=i=>{lastPhoneAction=Date.now();showPhone(i)};
 phoneDots.forEach((el,i)=>el.addEventListener('click',()=>selectPhone(i)));
 wings.forEach(wing=>wing.addEventListener('click',()=>selectPhone(Number(wing.dataset.scene))));
 const screen=q('.showcase-phone');screen.setAttribute('tabindex','0');screen.setAttribute('aria-label','Experiências com sua conexão. Toque para ver a próxima.');
 screen.addEventListener('click',()=>selectPhone(phoneIndex+1));
 screen.addEventListener('keydown',e=>{if(['ArrowRight','ArrowLeft','Enter',' '].includes(e.key)){e.preventDefault();selectPhone(phoneIndex+(e.key==='ArrowLeft'?-1:1))}});
 showPhone(0);
 new IntersectionObserver(entries=>{phoneVisible=entries[0].isIntersecting},{threshold:.15}).observe(q('.showcase-phone'));
 setInterval(()=>{if(phoneVisible&&!phonePaused&&!document.hidden&&Date.now()-lastPhoneAction>6000)showPhone((phoneIndex+1)%phoneScenes.length)},4500);
}
}());
