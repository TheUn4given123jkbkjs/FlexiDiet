const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const src=fs.readFileSync('public/assets/js/api-client.js','utf8');
let calls=[];
const replies={
 '/auth/csrf':{data:{csrf_token:'known-token'}},
 '/meals':{data:{id:5,name:'Bún chả'}}
};
const context=vm.createContext({
 fetch:async(url,options)=>{
   calls.push({url,options});
   const path=decodeURIComponent(new URL(url,'http://local/').searchParams.get('route'));
   const body=replies[path]||{data:{status:'ok'}};
   return {ok:true,status:200,json:async()=>body};
 },
 Error,JSON
});
vm.runInContext(src,context);
(async()=>{
 const api=vm.runInContext('FlexiAPI',context);
 const result=await api.request('/meals',{method:'POST',data:{name:'Bún chả'}});
 assert.equal(result.id,5);
 assert.equal(calls.length,2);
 assert.match(calls[0].url,/auth%2Fcsrf/);
 assert.equal(calls[1].options.headers['X-CSRF-Token'],'known-token');
 assert.equal(JSON.parse(calls[1].options.body).name,'Bún chả');
 console.log('PASS API client obtains CSRF then sends authenticated JSON request');
})().catch(err=>{console.error(err);process.exit(1)});
