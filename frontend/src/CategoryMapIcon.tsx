export const mapCategoryColors:Record<string,string>={'Agriculture':'#4c704b','Financial inclusion':'#7d291e','Green innovation':'#4f7465','Youth & employment':'#8b623b','Tourism':'#c25c16','Health':'#a84439'};
export function CategoryMapGlyph({category}:{category:string}){
 switch(category){
  case 'Agriculture':return <><path d="M12 21V4M12 10C5 10 5 5 5 5s7 0 7 5Zm0 6c7 0 7-5 7-5s-7 0-7 5Z"/><path d="M12 5c5 0 5-4 5-4s-5 0-5 4Z"/></>;
  case 'Financial inclusion':return <><rect x="3" y="6" width="18" height="14" rx="2"/><path d="M3 7V4h14M16 11h5v5h-5zM17.5 13.5h.5"/></>;
  case 'Green innovation':return <><path d="M19 3C7 3 3 8 5 15s16 6 14-12Z"/><path d="m4 21 11-12"/></>;
  case 'Youth & employment':return <><rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V3h8v4M3 12c5 4 13 4 18 0M10 14h4"/></>;
  case 'Tourism':return <><circle cx="12" cy="12" r="9"/><path d="m16 8-2 6-6 2 2-6 6-2Z"/></>;
  case 'Health':return <path d="M9 3h6v6h6v6h-6v6H9v-6H3V9h6Z"/>;
  default:return <><path d="M12 2 22 12 12 22 2 12Z"/><circle cx="12" cy="12" r="3"/></>;
 }
}
export default function CategoryMapIcon({category}:{category:string}){return <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true"><CategoryMapGlyph category={category}/></svg>}
