import BrandLogo from './BrandLogo';
import '@fontsource/montserrat/latin-400.css';
import '@fontsource/montserrat/latin-500.css';
import '@fontsource/montserrat/latin-600.css';
import '@fontsource/montserrat/latin-700.css';
import AwardedShowcase from './AwardedShowcase';
import {useEffect,useState} from 'react';
import {ArrowRight, Lightbulb, UsersThree, Plant, TrendUp, List, X, ArrowUpRight} from '@phosphor-icons/react';
import './public.css';

const portalLogin=(import.meta as ImportMeta & {env:{DEV:boolean}}).env.DEV?'/login':'https://ohprime-ceda-portal.up.railway.app/applicant';
const portalRegister=portalLogin;

const benefits=[{icon:UsersThree,title:'Create your account',text:'Register with your email address and password to get started.'},{icon:Lightbulb,title:'Tell us your idea',text:'Choose a category and development stage, then complete your online proposal.'},{icon:Plant,title:'Submit & follow progress',text:'Review your answers, submit your idea, and follow updates in your portal.'},{icon:TrendUp,title:'Get notified if awarded',text:'If your idea is selected for an award, receive the outcome and next steps.'}];
const categories=[{name:'Agriculture & food',text:'Fresh thinking for food production, farming, and stronger value chains.',image:'agriculture'},{name:'Enterprise & inclusion',text:'New ways to help small businesses grow and open up opportunities.',image:'enterprise'},{name:'Green innovation',text:'Practical ideas for a more resourceful and sustainable tomorrow.',image:'green'}];
const faqs=[
 {question:'How do I submit an idea?',answer:'Create an applicant account, choose the relevant category and business stage, then complete the online form. Review your answers and submit when the required information is in place.'},
 {question:'Can I save my application and finish it later?',answer:'Yes. Your work is saved as a draft, so you can return to the applicant portal and continue before submitting.'},
 {question:'What information will I need?',answer:'The form asks about you and your team, your idea, business evidence and your funding needs. Questions and supporting documents may vary by category and business stage.'},
 {question:'Can I change my idea after I submit it?',answer:'A submitted application becomes a read-only record. If a reviewer needs more information, you can respond to their request through the portal.'},
 {question:'How will I know what happens next?',answer:'Sign in to see your application stage, next action and portal notifications. Keep your contact details current so you can receive any available account messages.'},
 {question:'How are ideas reviewed?',answer:'Authorised evaluators assess submissions against programme criteria. An adjudicator can then consider completed assessments and record a reasoned decision.'},
 {question:'Where can I get help?',answer:'Use Ask CEDA for published programme and application guidance. For a question it cannot answer, use the contact details provided by CEDA.'},
];
export default function PublicWebsite(){
 useEffect(()=>{document.title='CEDA | Your idea. Botswana’s future.'},[]);
 const [menu,setMenu]=useState(false);
 const currentSlide={image:'/hero-market.webp',alt:'A small business owner celebrating in her fresh produce shop',title:'Your idea.',accent:'Botswana’s future.',description:'A better tomorrow starts with an idea today. Bring yours to life with the CEDA innovation portal.'};
 return <div className="public-site" id="top">
  <a className="public-skip" href="#public-content">Skip to content</a>
  <header className="public-header"><div className="public-container public-header-inner">
    <a className="public-brand" href="/" aria-label="CEDA home"><BrandLogo/></a>
    <button className="public-menu-toggle" aria-label={menu?'Close navigation':'Open navigation'} aria-expanded={menu} aria-controls="public-nav" onClick={()=>setMenu(!menu)}>{menu?<X size={25}/>:<List size={25}/>}</button>
    {menu&&<button type="button" className="public-menu-backdrop" aria-label="Close navigation" onClick={()=>setMenu(false)}/>}
    <nav id="public-nav" className={menu?'is-open':''} aria-label="Public navigation"><a href="#about" onClick={()=>setMenu(false)}>The programme</a><a href="#opportunities" onClick={()=>setMenu(false)}>Explore ideas</a><a href="#idea-journey" onClick={()=>setMenu(false)}>How to apply</a><a href="#awarded-ideas" onClick={()=>setMenu(false)}>Awarded ideas <ArrowUpRight size={15}/></a><a href="#faq" onClick={()=>setMenu(false)}>FAQ</a><a className="public-button public-mobile-cta" href={portalLogin}>SIGN IN <ArrowRight size={17}/></a></nav>
    <a className="public-button public-header-cta" href={portalLogin}>SIGN IN <ArrowRight size={17}/></a>
   </div></header>
  <section className="public-hero" aria-label="Ideas for Botswana">
   <div className="public-hero-images"><img className="public-hero-photo is-current" src={currentSlide.image} alt={currentSlide.alt} fetchPriority="high"/></div>
   <main id="public-content" className="public-container public-hero-content"><div aria-live="off" aria-atomic="true"><h1>{currentSlide.title}<br/><em>{currentSlide.accent}</em></h1><p className="public-hero-description">{currentSlide.description}</p></div><div className="public-hero-actions"><a className="public-button" href={portalRegister}>Access Portal <ArrowRight size={19}/></a><a className="public-text-link" href="#about">Discover the programme <ArrowDownIcon/></a></div><p className="public-hero-note">For the thinkers. The makers. The changemakers.</p></main>

  </section>
  <section id="about" className="public-container public-about"><p className="public-eyebrow">A PLATFORM FOR POSSIBILITY</p><div><h2>Your perspective could<br/>make all the difference.</h2><p>This platform connects Batswana innovators, entrepreneurs and problem-solvers directly to CEDA. Whether you’re a student with a bold concept, a business owner with a better way of working, or a first-time founder with something entirely new, you can submit your idea here and put it in front of the people who can help turn it into a funded, working reality.</p></div></section>
  <section id="idea-journey" className="public-container public-feature-section" aria-label="Your idea journey"><p className="public-feature-subtitle">How it works</p><div className="public-benefits">{benefits.map(({icon:Icon,title,text},i)=><article key={title}><span className="public-card-number">0{i+1}</span><Icon size={34} weight="light"/><h2>{title}</h2><p>{text}</p>{i===3&&<a className="public-feature-link" href={portalRegister} aria-label="Explore the applicant portal">Explore <ArrowUpRight size={17}/></a>}</article>)}</div></section>
  <section id="opportunities" className="public-container public-opportunities"><div className="public-section-heading"><div><p className="public-eyebrow">EXPLORE THE POSSIBILITIES</p><h2>Ideas That Build Botswana</h2></div></div><div className="public-category-grid">{categories.map(({name,text,image})=><article key={name}><div className={`public-category-photo ${image}`} role="img" aria-label={image==='agriculture'?'Farmers celebrating a corn harvest':image==='enterprise'?'A team working together around a laptop':'A woman harvesting fresh peppers in a field'}></div><div className="public-category-body"><h3>{name}</h3><p>{text}</p><a className="public-inline-link" href={portalRegister}>Explore <ArrowUpRight size={18}/></a></div></article>)}</div></section>
  <AwardedShowcase/>
  <section id="faq" className="public-faq public-container" aria-labelledby="public-faq-title"><div className="public-faq-intro"><p className="public-eyebrow">GOOD TO KNOW</p><h2 id="public-faq-title">Frequently asked questions</h2><p>Find answers to common questions about preparing, submitting and following your idea.</p></div><div className="public-faq-list">{faqs.map(({question,answer},i)=><details key={question} className="public-faq-item"><summary><span className="public-faq-number">{String(i+1).padStart(2,'0')}</span><span>{question}</span><span className="public-faq-toggle" aria-hidden="true">+</span></summary><p>{answer}</p></details>)}</div></section>
  <footer className="public-footer"><div className="public-container"><a className="public-brand" href="/" aria-label="CEDA home"><BrandLogo variant="dark"/></a><p>Ideas rooted in Botswana.<br/>Possibilities that reach further.</p></div><div className="public-container public-footer-bottom"><span>© {new Date().getFullYear()} CEDA. All rights reserved.</span><a href="#top">Back to top ↑</a></div></footer>
 </div>;
}
function ArrowDownIcon(){return <ArrowRight size={16} className="public-arrow-down"/>}
