(function(){
  'use strict';
  const form=document.querySelector('#signup'),cep=form.elements.cep,status=document.querySelector('#cep-status');
  const fields={endereco:'logradouro',bairro:'bairro',cidade:'localidade',estado:'uf'};
  let active='',lastLookup='',sequence=0,controller=null,automatic={};
  function notice(text){status.textContent=text}
  async function lookup(){
    const value=cep.value.replace(/\D/g,'');
    if(value.length!==8){notice(value?'Informe os 8 dígitos do CEP.':'Digite o CEP para buscar o endereço.');return}
    if(value===lastLookup)return;
    lastLookup=value;
    const version=++sequence,request=new AbortController();controller=request;
    const before={};Object.keys(fields).forEach(key=>before[key]=form.elements[key].value);
    const timeout=setTimeout(()=>request.abort(),8000);
    status.setAttribute('aria-busy','true');notice('Buscando endereço…');
    try{
      const response=await fetch('https://viacep.com.br/ws/'+value+'/json/',{signal:request.signal,credentials:'omit',referrerPolicy:'no-referrer'});
      if(!response.ok)throw new Error('http');
      const data=await response.json();
      if(version!==sequence||cep.value.replace(/\D/g,'')!==value)return;
      if(data.erro){notice('CEP não encontrado. Confira o número ou preencha o endereço manualmente.');return}
      if(!data.localidade||!data.uf)throw new Error('resposta');
      Object.entries(fields).forEach(([key,source])=>{
        const input=form.elements[key],next=String(data[source]||'');
        if(input.value===before[key]&&(!before[key]||automatic[key]===before[key])){
          input.value=next;automatic[key]=next;
        }
      });
      notice('CEP localizado. Confira o endereço e complete os campos que faltam.');
    }catch(error){
      if(version!==sequence)return;
      lastLookup='';notice('Não foi possível consultar o CEP. Preencha o endereço manualmente ou saia do campo para tentar novamente.');
    }finally{
      clearTimeout(timeout);
      if(version===sequence){controller=null;status.removeAttribute('aria-busy')}
    }
  }
  cep.addEventListener('input',()=>{
    const digits=cep.value.replace(/\D/g,'').slice(0,8);
    cep.value=digits.length>5?digits.slice(0,5)+'-'+digits.slice(5):digits;
    if(digits!==active){
      active=digits;sequence++;lastLookup='';
      if(controller)controller.abort();
      controller=null;status.removeAttribute('aria-busy');
      Object.keys(automatic).forEach(key=>{if(form.elements[key].value===automatic[key])form.elements[key].value=''});
      automatic={};notice('Digite os 8 dígitos do CEP para buscar o endereço.');
    }
    if(digits.length===8)lookup();
  });
  cep.addEventListener('blur',lookup);
}());
