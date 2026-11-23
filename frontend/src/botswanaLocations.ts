import data from './data/botswana-map.json';
import type {Idea} from './model';
export const districts=data.districts;
export type MapLocation={id:string;name:string;x:number;y:number;district:string;approximate:boolean};
export const normaliseLocation=(value:string)=>value.normalize('NFKD').replace(/[\u0300-\u036f]/g,'').toLowerCase().replace(/[^a-z0-9]+/g,' ').trim();
const names=new Map<string,typeof data.places>();
for(const place of data.places)for(const alias of place.aliases){const list=names.get(alias)||[];if(!list.some(p=>p.id===place.id))list.push(place);names.set(alias,list)}
export function locateSubmission(raw:string):MapLocation|null{
 if(!raw||/\b(south africa|namibia|zimbabwe|zambia)\b/i.test(raw))return null;
 const clean=normaliseLocation(raw).replace(/\bbotswana\b/g,'').trim();
 const matches=names.get(clean);
 if(matches?.length===1)return {...matches[0],approximate:false};
 const parts=raw.split(/[,;|]/).map(normaliseLocation);
 const candidates=parts.flatMap(part=>names.get(part)||[]);
 const unique=[...new Map(candidates.map(p=>[p.id,p])).values()];
 if(unique.length===1)return {...unique[0],approximate:false};
 if(unique.length>1||matches?.length)return null;
 const district=districts.find(d=>normaliseLocation(d.name)===clean.replace(/\bdistrict\b/g,'').trim()||(d.name==='North-West'&&clean==='ngamiland'));
 return district?{id:district.id,name:district.name+' district',x:district.label[0],y:district.label[1],district:district.id,approximate:true}:null;
}
export function mapSubmissions(ideas:Idea[],category='all'){
 const groups=new Map<string,{key:string;location:MapLocation;category:string;ideas:Idea[]}>(),unmapped:Idea[]=[];
 for(const idea of ideas){if(category!=='all'&&idea.category!==category)continue;const location=locateSubmission(idea.location);if(!location){unmapped.push(idea);continue}const key=location.id+'|'+idea.category;const group=groups.get(key)||{key,location,category:idea.category,ideas:[]};group.ideas.push(idea);groups.set(key,group)}
 return {groups:[...groups.values()],unmapped,mapped:[...groups.values()].reduce((n,g)=>n+g.ideas.length,0)};
}
