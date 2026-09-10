(function(){
  'use strict';
  const form=document.querySelector('#signup'),birth=form.elements.nascimento;
  birth.addEventListener('input',()=>{
    const position=birth.selectionStart,digitsBefore=birth.value.slice(0,position).replace(/\D/g,'').length;
    const digits=birth.value.replace(/\D/g,'').slice(0,8);
    birth.value=digits.slice(0,2)+(digits.length>2?'/'+digits.slice(2,4):'')+(digits.length>4?'/'+digits.slice(4):'');
    let cursor=0,count=0;while(cursor<birth.value.length&&count<digitsBefore){if(/\d/.test(birth.value[cursor]))count++;cursor++}
    birth.setSelectionRange(cursor,cursor);
  });
  const dialog=document.querySelector('#plan-picker'),rail=document.querySelector('#picker-rail'),status=document.querySelector('#picker-status');
  const previous=document.querySelector('#picker-previous'),next=document.querySelector('#picker-next'),range=document.querySelector('#picker-range'),retry=document.querySelector('#picker-retry');
  let plans=[],request=null,opener=null,oldOverflow='',generation=0;
  const text=(tag,value,className)=>{const el=document.createElement(tag);el.textContent=value;if(className)el.className=className;return el};
  function navigation(){
    const cards=[...rail.children],max=rail.scrollWidth-rail.clientWidth;
    previous.disabled=!cards.length||rail.scrollLeft<3;next.disabled=!cards.length||rail.scrollLeft>=max-3;
    if(!cards.length){range.textContent='';return}
    const step=cards[0].getBoundingClientRect().width+16;
    const first=Math.round(rail.scrollLeft/step)+1,visible=Math.max(1,Math.round(rail.clientWidth/step));
    range.textContent='Plano '+first+' de '+plans.length;
    if(visible>1)range.textContent='Planos '+first+'–'+Math.min(plans.length,first+visible-1)+' de '+plans.length;
  }
  function move(direction){
    const first=rail.firstElementChild;if(!first)return;
    rail.scrollBy({left:direction*(first.getBoundingClientRect().width+16),behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth'});
  }
  function choose(p){
    form.elements.plano.value=p.nome;document.querySelector('#selected-plan').textContent=p.nome;
    const url=new URL(location.href);url.searchParams.set('plano',p.nome);history.replaceState(null,'',url);
    dialog.close();
  }
  function render(){
    rail.replaceChildren();
    plans.forEach(p=>{
      const card=text('article','','picker-card'),current=p.nome===form.elements.plano.value;
      if(current)card.classList.add('is-selected');
      card.append(text('small',current?'Seu plano atual':'Plano disponível','picker-badge'),text('h3',p.nome));
      const price=Number(String(p.valor).replace(',','.')).toLocaleString('pt-BR',{style:'currency',currency:'BRL'});
      card.append(text('p',price+' / mês','picker-price'));
      const list=document.createElement('ul');
      String(p.descricao||'').split(/\r?\n/).filter(Boolean).slice(0,5).forEach(line=>list.append(text('li',line.replace(/^[-•]\s*/,''))));
      card.append(list);
      const button=text('button',current?'Manter este plano':'Selecionar plano','picker-select');button.type='button';button.addEventListener('click',()=>choose(p));
      card.append(button);rail.append(card);
    });
    status.textContent=plans.length?'':'Nenhum plano disponível no momento.';
    const selected=Math.max(0,plans.findIndex(p=>p.nome===form.elements.plano.value)),card=rail.children[selected];
    if(card)rail.scrollLeft=card.offsetLeft;
    navigation();
  }
  async function open(){
    if(!dialog.open){opener=document.activeElement;oldOverflow=document.body.style.overflow;document.body.style.overflow='hidden';dialog.showModal()}
    if(request)request.abort();request=new AbortController();const activeRequest=request,version=++generation;
    const timeout=setTimeout(()=>activeRequest.abort(),10000);
    rail.replaceChildren();plans=[];navigation();status.textContent='Carregando planos…';retry.hidden=true;
    try{
      const response=await fetch('/abgs-data.php',{cache:'no-store',signal:activeRequest.signal});
      if(!response.ok)throw new Error('http');
      const data=await response.json();if(data.ok===false||!Array.isArray(data.planos))throw new Error('dados');
      if(version!==generation||!dialog.open)return;
      plans=data.planos;render();
    }catch(error){if(version===generation&&dialog.open){status.textContent='Não foi possível carregar os planos. Tente novamente.';retry.hidden=false}}
    finally{clearTimeout(timeout)}
  }
  document.querySelectorAll('[data-open-plan-picker]').forEach(el=>el.addEventListener('click',open));
  document.querySelector('#picker-close').addEventListener('click',()=>dialog.close());
  previous.addEventListener('click',()=>move(-1));next.addEventListener('click',()=>move(1));retry.addEventListener('click',open);
  rail.addEventListener('scroll',navigation,{passive:true});new ResizeObserver(navigation).observe(rail);
  rail.addEventListener('keydown',e=>{if(e.target===rail&&(e.key==='ArrowRight'||e.key==='ArrowLeft')){e.preventDefault();move(e.key==='ArrowRight'?1:-1)}});
  dialog.addEventListener('click',e=>{const r=dialog.getBoundingClientRect();if(e.target===dialog&&(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom))dialog.close()});
  dialog.addEventListener('close',()=>{generation++;if(request)request.abort();document.body.style.overflow=oldOverflow;if(opener&&opener.isConnected)opener.focus()});
}());
