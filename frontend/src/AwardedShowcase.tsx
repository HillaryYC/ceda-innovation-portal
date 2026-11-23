import {useEffect,useRef,useState} from 'react';
import {ArrowLeft,ArrowRight,X} from '@phosphor-icons/react';
import {awardedStories,type AwardedStory} from './awardedStories';
import './awarded-showcase.css';

export default function AwardedShowcase(){
 const [activeIndex,setActiveIndex]=useState(0);
 const [selected,setSelected]=useState<AwardedStory|null>(null);
 const [published,setPublished]=useState<{id:number;title:string;category:string;summary:string}[]>([]);
 const dialog=useRef<HTMLDialogElement>(null);
 const stories:AwardedStory[]=[...published.map(idea=>({slug:`idea-${idea.id}`,title:idea.title,category:idea.category,excerpt:idea.summary,story:idea.summary,image:/agri|farm|food|poultry/i.test(idea.category)?'/category-agriculture.webp':/green|energy|climate/i.test(idea.category)?'/category-green.webp':'/category-enterprise.webp',imageAlt:`CEDA recognised idea in ${idea.category}`})),...awardedStories];

 useEffect(()=>{let active=true;fetch('/api/v1/public/awarded-ideas',{headers:{Accept:'application/json'}}).then(response=>response.ok?response.json():{data:[]}).then(result=>{if(active)setPublished(result.data||[])}).catch(()=>{});return()=>{active=false}},[]);

 useEffect(()=>{
  if(stories.length<2||window.matchMedia?.('(prefers-reduced-motion: reduce)').matches)return;
  const timer=window.setInterval(()=>setActiveIndex(index=>(index+1)%stories.length),6500);
  return ()=>window.clearInterval(timer);
 },[stories.length]);
 useEffect(()=>{
  if(selected&&!dialog.current?.open)dialog.current?.showModal();
  else if(!selected&&dialog.current?.open)dialog.current.close();
 },[selected]);

 const featured=stories[activeIndex]||stories[0];
 const firstParagraph=featured?.story.split(/\n\n+/)[0];
 const showControls=stories.length>1;

 return <section id="awarded-ideas" className="public-showcase" aria-labelledby="awarded-heading"><div className="public-container">
  <header className="public-showcase-heading"><p className="public-eyebrow">IDEAS IN THE SPOTLIGHT</p><h2 id="awarded-heading">Meet the ideas recognised by CEDA and the people bringing them to life.</h2></header>
  {!featured&&<p className="public-showcase-empty">Recognised ideas will appear here after CEDA publishes them.</p>}
  {featured&&<div className="public-showcase-carousel" role="group" aria-label="Awarded ideas" aria-roledescription="carousel">
   <div className="public-showcase-lead" key={featured.slug}>
    <button className="public-showcase-photo" type="button" onClick={()=>setSelected(featured)} aria-label={`Read ${featured.title}`}>
     <img src={featured.image} alt={featured.imageAlt} className={featured.imageFit==='contain'?'is-contained':undefined} style={{objectPosition:featured.imagePosition}}/>
    </button>
    <article className="public-showcase-article"><h3>{featured.title}</h3><p>{firstParagraph}</p><button type="button" onClick={()=>setSelected(featured)}>Read about this idea <ArrowRight size={18}/></button></article>
   </div>
   {showControls&&<div className="public-showcase-controls"><span className="public-showcase-count">{String(activeIndex+1).padStart(2,'0')} / {String(stories.length).padStart(2,'0')}</span><div className="public-showcase-dots" aria-label="Choose a recognised idea">{stories.map((story,index)=><button key={story.slug} type="button" className={index===activeIndex?'is-active':''} aria-label={`Show ${story.title}`} aria-current={index===activeIndex?'true':undefined} onClick={()=>setActiveIndex(index)}/>)}</div><div className="public-showcase-arrows"><button type="button" aria-label="Previous awarded idea" onClick={()=>setActiveIndex(index=>(index-1+stories.length)%stories.length)}><ArrowLeft size={18}/></button><button type="button" aria-label="Next awarded idea" onClick={()=>setActiveIndex(index=>(index+1)%stories.length)}><ArrowRight size={18}/></button></div></div>}
  </div>}
 </div><dialog ref={dialog} className="public-story-dialog" onClose={()=>setSelected(null)} aria-label={selected?.title||'Award story'}>{selected&&<article><button className="public-story-close" type="button" onClick={()=>setSelected(null)} aria-label="Close story"><X size={22}/></button><img src={selected.image} alt={selected.imageAlt} className={selected.imageFit==='contain'?'is-contained':undefined}/><div className="public-story-content"><span className="public-showcase-tag">AWARDED IDEA · {selected.category}</span><h2>{selected.title}</h2><p className="public-story-intro">{selected.excerpt}</p><div className="public-story-body">{selected.story.split(/\n\n+/).map((paragraph,index)=><p key={index}>{paragraph}</p>)}</div></div></article>}</dialog></section>;
}

