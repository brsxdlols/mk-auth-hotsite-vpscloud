(function(){'use strict';const q=(s,p=document)=>p.querySelector(s),qa=(s,p=document)=>[...p.querySelectorAll(s)];q('#year').textContent=new Date().getFullYear();const menu=q('.menu-btn'),nav=q('.nav nav');menu.addEventListener('click',()=>{const open=nav.classList.toggle('open');menu.setAttribute('aria-expanded',open)});qa('.nav nav a').forEach(a=>a.addEventListener('click',()=>nav.classList.remove('open')));
const dateFormatter=new Intl.DateTimeFormat('pt-BR',{day:'2-digit',month:'2-digit',year:'numeric'}),timeFormatter=new Intl.DateTimeFormat('pt-BR',{hour:'2-digit',minute:'2-digit',second:'2-digit'});function updateClock(){const now=new Date(),date=q('#live-date'),clock=q('#live-clock');if(date)date.textContent=dateFormatter.format(now);if(clock)clock.textContent=timeFormatter.format(now)}updateClock();setInterval(updateClock,1000);fetch('abgs-visitor.php',{cache:'no-store',credentials:'same-origin'}).then(r=>{if(!r.ok)throw new Error('visitor');return r.json()}).then(v=>{q('#visitor-ip').textContent=v.ip||'não identificado';q('#visitor-count').textContent=Number(v.count||0).toLocaleString('pt-BR')}).catch(()=>{q('#visitor-ip').textContent='indisponível';q('#visitor-count').textContent='—'});
const slides=qa('.hero-bg'),dots=q('.hero-dots');let active=0;slides.forEach((_,i)=>{const b=document.createElement('button');b.className=i===0?'active':'';b.onclick=()=>show(i);dots.appendChild(b)});function show(i){slides[active].classList.remove('active');dots.children[active].classList.remove('active');active=i;slides[active].classList.add('active');dots.children[active].classList.add('active')}if(slides.length)setInterval(()=>show((active+1)%slides.length),6000);
let whatsappNumber='',cadastroRoute='cadastro-sistema.php';fetch('abgs-data.php',{cache:'no-store'}).then(r=>{if(!r.ok)throw new Error('dados');return r.json()}).then(data=>{if(data.ok===false||!Array.isArray(data.planos))throw new Error('dados');const e=data.empresa||{},mode=(data.config||{}).cadastro_modo;cadastroRoute=mode==='whatsapp'?'cadastro-whatsapp.hhvm':'cadastro-sistema.php';qa('[data-name]').forEach(x=>x.textContent=e.nome||'Provedor de Internet');qa('[data-phone]').forEach(x=>x.textContent=e.fone||e.celular||'Fale conosco');qa('[data-email]').forEach(x=>x.textContent=e.email||'');qa('[data-phone-link]').forEach(x=>x.href='tel:'+(e.fone||e.celular||'').replace(/\D/g,''));qa('[data-email-link]').forEach(x=>x.href='mailto:'+(e.email||''));whatsappNumber=(e.whatsapp||e.celular||e.fone||'').replace(/\D/g,'').replace(/^55/,'');qa('[data-whatsapp]').forEach(x=>x.href=whatsappNumber?'https://wa.me/55'+whatsappNumber:'#atendimento');document.title=e.nome||document.title;renderPlans(data.planos||[]);updatePlanNav()}).catch(()=>(q('#plans')||q('#all-plans')).innerHTML='<div class="loading">Não foi possível carregar os planos. Tente atualizar a página ou entre em contato.</div>');
function planVisual(name){
  const type=/cloud|vps|ssd|ram|cpu/i.test(String(name))?'cloud':/api|wpp|whats|integra/i.test(String(name))?'api':/fibra|mega|mb|internet|wifi|wi-fi|giga|^\d+$/i.test(String(name))?'network':'service';
  const art={
    network:'<path class="signal" d="M49 43Q96 2 143 43M65 58Q96 31 127 58M81 72Q96 59 111 72"/><circle class="light" cx="96" cy="83" r="4"/><path d="M51 95V75M141 95V75"/><rect class="device" x="34" y="95" width="124" height="32" rx="10"/><path d="M47 127v6M145 127v6"/><circle class="light" cx="51" cy="111" r="3"/><circle class="light" cx="64" cy="111" r="3"/><path d="M119 110h24"/>',
    cloud:'<path class="signal" d="M61 37H49a14 14 0 0 1-1-28 25 25 0 0 1 46 0 14 14 0 0 1 2 28H84"/><path class="connector" d="M72 37v12h24v12"/><rect class="device" x="38" y="61" width="118" height="30" rx="8"/><rect class="device" x="38" y="101" width="118" height="30" rx="8"/><circle class="light" cx="53" cy="76" r="3"/><circle class="light" cx="53" cy="116" r="3"/><path d="M73 76h26M116 72h23M116 80h23M73 116h26M116 112h23M116 120h23"/>',
    api:'<path class="connector" d="M55 47h37v50h37M92 47h37M55 111h37V97"/><rect class="device" x="13" y="23" width="44" height="44" rx="11"/><rect class="device" x="130" y="23" width="44" height="44" rx="11"/><rect class="device" x="13" y="89" width="44" height="44" rx="11"/><rect class="device" x="130" y="77" width="44" height="44" rx="11"/><path class="signal" d="m29 36-7 9 7 9m12-18 7 9-7 9M143 45l6 6 12-13M26 104h18M26 116h11M143 99h18m-9-9v18"/><circle class="light" cx="92" cy="73" r="5"/>'
  };
  art.service='<path class="signal" d="M49 81V65a47 47 0 0 1 94 0v16"/><rect class="device" x="37" y="71" width="25" height="40" rx="10"/><rect class="device" x="130" y="71" width="25" height="40" rx="10"/><path d="M142 112v6a12 12 0 0 1-12 12h-19"/><rect class="device" x="84" y="122" width="28" height="14" rx="7"/><path class="connector" d="M78 73h36M78 85h23"/>';
  const labels={service:['Atendimento','Serviços','Soluções para você'],network:['Conectividade','Internet','Seu dia mais conectado'],cloud:['Infraestrutura','Cloud','Espaço para suas ideias'],api:['Tecnologia','Integrações','Seus sistemas conectados']};
  const label=labels[type];
  return `<div class="plan-visual ${type}"><div class="plan-heading"><small>${label[0]}</small><b>${label[1]}</b><span class="plan-caption">${label[2]}</span></div><svg class="plan-art" viewBox="0 0 192 144" aria-hidden="true" focusable="false">${art[type]}</svg></div>`;
}
function renderPlans(plans){const box=q('#plans')||q('#all-plans');if(!plans.length){box.innerHTML='<div class="loading">Consulte nossos planos pelo atendimento.</div>';return}box.innerHTML=plans.map((p,i)=>{const lines=(p.descricao||'Internet estável\nAtendimento próximo').split(/\r?\n/).filter(Boolean).slice(0,4);const price=Number(String(p.valor).replace(',','.')).toLocaleString('pt-BR',{minimumFractionDigits:2});return `<article class="plan">${planVisual(p.nome,i)}<div class="plan-body"><h3 title="${esc(p.nome)}">${esc(p.nome)}</h3><div class="price"><small>R$</small><b>${price}</b><small>/mês</small></div><ul>${lines.map(x=>`<li>${esc(x.replace(/^[-•]\s*/,''))}</li>`).join('')}</ul><a class="btn primary" href="${cadastroRoute}?plano=${encodeURIComponent(p.nome)}">Selecionar plano</a></div></article>`}).join('');if(q('#all-plans'))q('#all-plans').innerHTML=box.innerHTML}
function esc(v){return String(v||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}
const plansRail=q('#plans'),planControls=q('.plan-controls'),previousPlan=q('.slider-arrow.prev'),nextPlan=q('.slider-arrow.next'),planRange=q('#plan-range');
let selectedPlan=0;
function updatePlanNav(){
 if(!plansRail||!planControls)return;
 const cards=qa('.plan',plansRail),count=cards.length;planControls.hidden=count<2;if(!count)return;
 selectedPlan=(selectedPlan+count)%count;
 cards.forEach((card,index)=>{const offset=(index-selectedPlan+count)%count,position=offset===0?'current':offset===1?'right':offset===count-1?'left':'hidden';card.dataset.position=position;card.inert=position==='hidden';card.setAttribute('aria-hidden',position==='hidden'?'true':'false')});
 planRange.textContent='Plano '+(selectedPlan+1)+' de '+count;
}
let planAnimating=false,pendingPlanMoves=0;
async function movePlans(direction){
 if(planAnimating){pendingPlanMoves+=direction;return}
 const cards=qa('.plan',plansRail);
 if(cards.length<2)return;
 const reduced=matchMedia('(prefers-reduced-motion: reduce)').matches;
 const before=new Map(cards.filter(card=>card.getClientRects().length).map(card=>[card,card.getBoundingClientRect()]));
 selectedPlan+=direction;updatePlanNav();
 if(reduced)return;
 planAnimating=true;
 const railRect=plansRail.getBoundingClientRect(),animations=[],ghosts=[];
 const visible=cards.filter(card=>card.getClientRects().length);
 const step=matchMedia('(max-width:820px)').matches?railRect.width:(railRect.width+18)/3;
 visible.forEach(card=>{
  const end=card.getBoundingClientRect(),old=before.get(card),transform=getComputedStyle(card).transform;
  const dx=old?old.left+old.width/2-end.left-end.width/2:direction*step;
  const dy=old?old.top+old.height/2-end.top-end.height/2:0;
  const ratio=old?old.width/end.width:1;
  animations.push(card.animate([{transform:'translate('+dx+'px,'+dy+'px) scale('+ratio+') '+transform,opacity:old?1:0},{transform,opacity:1}],{duration:460,easing:'cubic-bezier(.22,.7,.2,1)'}));
 });
 before.forEach((rect,card)=>{
  if(visible.includes(card))return;
  const ghost=card.cloneNode(true);ghost.classList.add('plan-ghost');ghost.inert=true;ghost.setAttribute('aria-hidden','true');
  Object.assign(ghost.style,{position:'absolute',display:'flex',left:(rect.left-railRect.left)+'px',top:(rect.top-railRect.top)+'px',width:rect.width+'px',height:rect.height+'px',transform:'none',margin:'0',pointerEvents:'none'});
  plansRail.appendChild(ghost);ghosts.push(ghost);
  animations.push(ghost.animate([{transform:'translateX(0)',opacity:1},{transform:'translateX('+(-direction*step)+'px)',opacity:0}],{duration:460,easing:'cubic-bezier(.22,.7,.2,1)',fill:'forwards'}));
 });
 await Promise.allSettled(animations.map(animation=>animation.finished));
 ghosts.forEach(ghost=>ghost.remove());planAnimating=false;
 if(pendingPlanMoves){const next=Math.sign(pendingPlanMoves);pendingPlanMoves-=next;movePlans(next)}
}
if(nextPlan)nextPlan.onclick=()=>movePlans(1);if(previousPlan)previousPlan.onclick=()=>movePlans(-1);
plansRail?.addEventListener('keydown',e=>{if(e.key==='ArrowLeft'||e.key==='ArrowRight'){e.preventDefault();movePlans(e.key==='ArrowRight'?1:-1)}});
let touchStart=null;
plansRail?.addEventListener('touchstart',e=>{touchStart={x:e.touches[0].clientX,y:e.touches[0].clientY}},{passive:true});
plansRail?.addEventListener('touchend',e=>{if(!touchStart)return;const dx=e.changedTouches[0].clientX-touchStart.x,dy=e.changedTouches[0].clientY-touchStart.y;if(Math.abs(dx)>45&&Math.abs(dx)>Math.abs(dy))movePlans(dx<0?1:-1);touchStart=null},{passive:true});
q('#contact-form')?.addEventListener('submit',e=>{e.preventDefault();const f=new FormData(e.currentTarget),parts=[`Olá! Meu nome é ${f.get('nome')}.`,`Assunto: ${f.get('assunto')}.`,`Telefone: ${f.get('telefone')}.`,f.get('email')?`E-mail: ${f.get('email')}.`:'',f.get('bairro')?`Bairro/localidade: ${f.get('bairro')}.`:'',f.get('endereco')?`Endereço: ${f.get('endereco')}.`:'',f.get('mensagem')?`Mensagem: ${f.get('mensagem')}`:''].filter(Boolean);if(whatsappNumber)window.open('https://wa.me/55'+whatsappNumber+'?text='+encodeURIComponent(parts.join('\n')),'_blank');else location.href='mailto:?subject='+encodeURIComponent(f.get('assunto'))+'&body='+encodeURIComponent(parts.join('\n'))});
const observer=new IntersectionObserver(entries=>entries.forEach(entry=>{if(entry.isIntersecting){entry.target.classList.add('in-view');observer.unobserve(entry.target)}}),{threshold:.15});qa('.reveal,.feature').forEach(el=>observer.observe(el));
}());
