const fs=require('fs'),vm=require('vm'),assert=require('assert');
const source=fs.readFileSync(__dirname+'/../theme/midias_vpscloud/js/modern-vpscloud.js','utf8');
const context=vm.createContext({planVisualMode:'dynamic'});
const fn=vm.runInContext('('+source.slice(source.indexOf('function planVisual('),source.indexOf('function renderPlans(')).trim()+')',context);
for(const [name,type] of [['Combo Fibra 500 Mega','combo'],['Rural 100','rural'],['Internet Sítio','rural'],['Fibra Cidade','fiber'],['Internet 100 Mega','network'],['Cidade','service'],['CONSULTORIA_3HORAS','service'],['CLOUD_50_CLIENTES_SSD','cloud'],['API_WPP_MKAUTH','api'],['CFT_ANATEL','regulatory'],['LICENÇA_MKAUTH','license'],['SUPORTE_CLOUD','support'],['Serviço Internet','service'],['HOSPEDAGEM','cloud'],['GPON_500','fiber'],['Link dedicado','network']]){
 assert(fn(name).includes('plan-visual '+type),name);
}
for(const forced of ['network','service','combo','cloud','fiber','rural','api','license','support','regulatory']){
 context.planVisualMode=forced;
 assert(fn('CFT LICENÇA SUPORTE CLOUD').includes('plan-visual '+forced),forced);
}
console.log('PASS: 16 dynamic names and 10 fixed image categories');
