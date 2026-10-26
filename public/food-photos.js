(() => {
 const dishes=window.restaurantMenu.filter(dish=>dish.photo_search);
 const searches=[...new Set(dishes.map(dish=>dish.photo_search))];
 async function loadPhoto(search){
  const controller=new AbortController();
  const timeout=setTimeout(()=>controller.abort(),12000);
  try{
   const response=await fetch(`https://www.themealdb.com/api/json/v1/1/search.php?${new URLSearchParams({s:search})}`,{signal:controller.signal,credentials:'omit',referrerPolicy:'no-referrer'});
   if(!response.ok)throw Error('Photo API unavailable');
   const data=await response.json();
   const match=Array.isArray(data.meals)?data.meals.find(meal=>typeof meal.strMeal==='string'&&meal.strMeal.toLowerCase()===search.toLowerCase()):null;
   if(!match||typeof match.strMealThumb!=='string')throw Error('Recipe photo missing');
   const image=new URL(match.strMealThumb);
   if(image.protocol!=='https:'||image.hostname!=='www.themealdb.com')throw Error('Invalid photo URL');
   dishes.filter(dish=>dish.photo_search===search).forEach(dish=>{dish.image=image.href;});
   window.dispatchEvent(new Event('menu-photos-updated'));
  }catch{
   // Keep the food illustration visible when the external API is unavailable.
  }finally{clearTimeout(timeout);}
 }
 Promise.allSettled(searches.map(loadPhoto));
})();
