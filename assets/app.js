(() => {
  'use strict';
  const $ = (s, p=document) => p.querySelector(s);
  const $$ = (s, p=document) => [...p.querySelectorAll(s)];
  const state = { csrf:'', user:null, mode:'chat', conversationId:null, busy:false, controller:null, ai:null };
  const els = {
    sidebar:$('#sidebar'), backdrop:$('#sidebarBackdrop'), menu:$('#menuButton'), sideClose:$('#sidebarClose'),
    newChat:$('#newChat'), history:$('#historyList'), historySearch:$('#historySearch'), refresh:$('#refreshHistory'),
    welcome:$('#welcome'), messages:$('#messages'), scroll:$('#chatScroll'), prompt:$('#prompt'), composer:$('#composer'), send:$('#sendButton'),
    imageBanner:$('#imageModeBanner'), imageSize:$('#imageSize'), imageMode:$('#imageModeButton'), exitImage:$('#exitImageMode'), modeText:$('#modeText'),
    authModal:$('#authModal'), authClose:$('#authClose'), authError:$('#authError'), loginForm:$('#loginForm'), registerForm:$('#registerForm'),
    loginTop:$('#loginTop'), profile:$('#profileButton'), sideName:$('#sidebarName'), sideEmail:$('#sidebarEmail'), sideAvatar:$('#sidebarAvatar'),
    modelName:$('#modelName'), modelType:$('#modelType'), provider:$('#providerLabel'), theme:$('#themeToggle'), clear:$('#clearChat'), toasts:$('#toasts')
  };

  const icons = {
    chat:'<svg viewBox="0 0 24 24"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/></svg>',
    image:'<svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="9" cy="9" r="2"/><path d="m21 15-5-5L5 21"/></svg>',
    copy:'<svg viewBox="0 0 24 24"><rect x="8" y="8" width="11" height="11" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/></svg>',
    retry:'<svg viewBox="0 0 24 24"><path d="M20 11a8 8 0 1 0-2 5.3"/><path d="M20 4v7h-7"/></svg>'
  };

  function esc(s=''){return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));}
  function nl2br(s){return s.replace(/\n/g,'<br>');}
  function formatDate(s){try{return new Intl.DateTimeFormat('fa-IR',{month:'short',day:'numeric'}).format(new Date(s));}catch{return '';}}
  function toast(msg,type=''){const t=document.createElement('div');t.className='toast '+type;t.textContent=msg;els.toasts.append(t);setTimeout(()=>t.remove(),3500);}
  function scrollBottom(){requestAnimationFrame(()=>els.scroll.scrollTo({top:els.scroll.scrollHeight,behavior:'smooth'}));}
  function setBusy(v){state.busy=v;els.send.classList.toggle('busy',v);els.send.disabled=false;els.prompt.disabled=v;if(!v)els.prompt.focus();}
  function api(url,opts={}){return fetch(url,{credentials:'same-origin',headers:{'Content-Type':'application/json',...(opts.headers||{})},...opts}).then(async r=>{const d=await r.json().catch(()=>({error:'پاسخ سرور قابل خواندن نیست.'}));if(!r.ok)throw Object.assign(new Error(d.error||'خطای سرور'),{status:r.status,data:d});return d;});}

  async function init(){
    applyTheme(localStorage.getItem('cora-theme')||'dark');
    bind();
    try{const s=await api('api/session.php');state.csrf=s.csrf;state.user=s.user;state.ai=s.ai;renderSession();if(state.user)await loadHistory();}
    catch(e){toast('اتصال به سرور برقرار نشد.','error');}
  }

  function renderSession(){
    if(state.ai){els.modelName.textContent=state.ai.text_model||'Cora';els.modelType.textContent=state.ai.provider==='ollama'?'مدل محلی Ollama':'OpenAI Compatible';els.provider.textContent=`${state.ai.provider} · ${state.ai.text_model}`;}
    if(state.user){
      const n=state.user.name||'کاربر';els.sideName.textContent=n;els.sideEmail.textContent=state.user.email;els.sideAvatar.textContent=n.trim().charAt(0).toUpperCase();els.loginTop.textContent=n;els.loginTop.classList.add('signed');
    }else{els.sideName.textContent='حساب کاربری';els.sideEmail.textContent='برای ذخیره تاریخچه وارد شوید';els.sideAvatar.textContent='C';els.loginTop.textContent='ورود / ثبت‌نام';els.history.innerHTML='<div class="empty-history">برای دیدن تاریخچه وارد حساب شوید.</div>';}
  }

  function bind(){
    els.menu.addEventListener('click',openSidebar);els.sideClose.addEventListener('click',closeSidebar);els.backdrop.addEventListener('click',closeSidebar);
    els.newChat.addEventListener('click',newChat);$('#brandHome').addEventListener('click',newChat);els.clear.addEventListener('click',()=>{els.messages.innerHTML='';els.welcome.classList.remove('hidden');toast('صفحه گفتگو پاک شد.');});
    els.refresh.addEventListener('click',()=>state.user&&loadHistory());
    let st;els.historySearch.addEventListener('input',()=>{clearTimeout(st);st=setTimeout(()=>loadHistory(els.historySearch.value),260);});
    els.composer.addEventListener('submit',e=>{e.preventDefault();state.busy?stopRequest():sendCurrent();});
    els.prompt.addEventListener('input',autoGrow);els.prompt.addEventListener('keydown',e=>{if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();state.busy?stopRequest():sendCurrent();}});
    document.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='k'){e.preventDefault();newChat();}});
    els.imageMode.addEventListener('click',()=>setMode('image'));els.exitImage.addEventListener('click',()=>setMode('chat'));
    $$('[data-mode]').forEach(b=>b.addEventListener('click',()=>{setMode(b.dataset.mode);if(innerWidth<921)closeSidebar();els.prompt.focus();}));
    $$('.suggestion').forEach(b=>b.addEventListener('click',()=>{if(b.dataset.mode)setMode(b.dataset.mode);els.prompt.value=b.dataset.prompt||'';autoGrow();els.prompt.focus();}));
    els.loginTop.addEventListener('click',()=>state.user?profileMenu():openAuth());els.profile.addEventListener('click',()=>state.user?profileMenu():openAuth());
    els.authClose.addEventListener('click',closeAuth);els.authModal.addEventListener('click',e=>{if(e.target===els.authModal)closeAuth();});
    $$('[data-auth-tab]').forEach(b=>b.addEventListener('click',()=>switchAuth(b.dataset.authTab)));
    els.loginForm.addEventListener('submit',e=>authSubmit(e,'login'));els.registerForm.addEventListener('submit',e=>authSubmit(e,'register'));
    els.theme.addEventListener('click',()=>applyTheme(document.body.classList.contains('light')?'dark':'light'));
    $('#attachButton').addEventListener('click',()=>toast('پیوست فایل در نسخه بعدی فعال می‌شود.'));
  }

  function openSidebar(){els.sidebar.classList.add('open');els.backdrop.classList.add('show');}
  function closeSidebar(){els.sidebar.classList.remove('open');els.backdrop.classList.remove('show');}
  function autoGrow(){els.prompt.style.height='auto';els.prompt.style.height=Math.min(els.prompt.scrollHeight,170)+'px';}
  function applyTheme(t){document.body.classList.toggle('light',t==='light');localStorage.setItem('cora-theme',t);}
  function setMode(mode){state.mode=mode==='image'?'image':'chat';els.imageBanner.classList.toggle('show',state.mode==='image');els.imageMode.classList.toggle('active',state.mode==='image');els.modeText.textContent=state.mode==='image'?'ساخت تصویر AI':'پاسخ هوشمند';els.prompt.placeholder=state.mode==='image'?'تصویری که می‌خواهی را دقیق توصیف کن...':'از Cora بپرس...';$$('.quick-item').forEach(b=>b.classList.toggle('active',b.dataset.mode===state.mode));}

  function openAuth(){els.authModal.classList.add('show');els.authError.textContent='';setTimeout(()=>$('#loginForm input')?.focus(),80);}
  function closeAuth(){els.authModal.classList.remove('show');}
  function switchAuth(tab){$$('[data-auth-tab]').forEach(b=>b.classList.toggle('active',b.dataset.authTab===tab));els.loginForm.classList.toggle('hidden',tab!=='login');els.registerForm.classList.toggle('hidden',tab!=='register');els.authError.textContent='';}
  async function authSubmit(e,action){
    e.preventDefault();els.authError.textContent='';const form=e.currentTarget,btn=$('button[type=submit]',form),fd=new FormData(form),body=Object.fromEntries(fd.entries());body.action=action;body.csrf=state.csrf;btn.disabled=true;
    try{const d=await api('api/auth.php',{method:'POST',body:JSON.stringify(body)});state.user=d.user;state.csrf=d.csrf;renderSession();closeAuth();form.reset();toast(action==='login'?'با موفقیت وارد شدی.':'حساب با موفقیت ساخته شد.','success');await loadHistory();}
    catch(err){els.authError.textContent=err.message;if(err.status===419)refreshSession();}finally{btn.disabled=false;}
  }
  async function refreshSession(){try{const s=await api('api/session.php');state.csrf=s.csrf;state.user=s.user;renderSession();}catch{}}
  function profileMenu(){
    const logout=confirm(`حساب: ${state.user.name}\n${state.user.email}\n\nبرای خروج از حساب OK را بزن.`);if(logout)logoutUser();
  }
  async function logoutUser(){try{await api('api/auth.php',{method:'POST',body:JSON.stringify({action:'logout',csrf:state.csrf})});state.user=null;state.conversationId=null;await refreshSession();newChat(false);toast('از حساب خارج شدی.');}catch(e){toast(e.message,'error');}}

  async function loadHistory(q=''){
    if(!state.user)return;
    try{const d=await api('api/history.php'+(q?'?q='+encodeURIComponent(q):''));renderHistory(d.conversations||[]);}catch(e){els.history.innerHTML='<div class="empty-history">خطا در دریافت تاریخچه</div>';}
  }
  function renderHistory(list){
    if(!list.length){els.history.innerHTML='<div class="empty-history">هنوز گفتگویی اینجا نیست.</div>';return;}
    els.history.innerHTML='';
    for(const c of list){const item=document.createElement('div');item.className='history-item'+(Number(c.id)===Number(state.conversationId)?' active':'');item.dataset.id=c.id;
      item.innerHTML=`<span class="mode-icon">${c.mode==='image'?icons.image:icons.chat}</span><span class="h-copy"><b>${c.pinned==1?'◆ ':''}${esc(c.title)}</b><small>${formatDate(c.updated_at)} · ${esc(c.model||'Cora')}</small></span><button class="more" type="button" aria-label="گزینه‌ها">•••</button>`;
      item.addEventListener('click',e=>{if(!e.target.closest('.more'))loadConversation(c.id,c.mode);});$('.more',item).addEventListener('click',e=>{e.stopPropagation();historyActions(c);});els.history.append(item);
    }
  }
  async function historyActions(c){
    const action=prompt(`عملیات گفتگو:\n1 = تغییر نام\n2 = ${c.pinned==1?'برداشتن پین':'پین کردن'}\n3 = حذف`,'1');if(!action)return;
    try{
      if(action==='1'){const title=prompt('عنوان جدید:',c.title);if(!title)return;await api('api/history.php',{method:'POST',body:JSON.stringify({action:'rename',id:c.id,title,csrf:state.csrf})});}
      else if(action==='2')await api('api/history.php',{method:'POST',body:JSON.stringify({action:'pin',id:c.id,pinned:c.pinned!=1,csrf:state.csrf})});
      else if(action==='3'){if(!confirm('این گفتگو حذف شود؟'))return;await api('api/history.php',{method:'POST',body:JSON.stringify({action:'delete',id:c.id,csrf:state.csrf})});if(Number(state.conversationId)===Number(c.id))newChat(false);}
      await loadHistory(els.historySearch.value);
    }catch(e){toast(e.message,'error');}
  }
  async function loadConversation(id,mode='chat'){
    try{const d=await api('api/history.php?id='+encodeURIComponent(id));state.conversationId=Number(id);setMode(mode);els.messages.innerHTML='';els.welcome.classList.add('hidden');for(const m of d.messages||[])renderStoredMessage(m);await loadHistory(els.historySearch.value);closeSidebar();scrollBottom();}
    catch(e){toast(e.message,'error');}
  }
  function newChat(focus=true){if(state.busy)stopRequest();state.conversationId=null;els.messages.innerHTML='';els.welcome.classList.remove('hidden');setMode('chat');if(state.user)loadHistory(els.historySearch.value);closeSidebar();if(focus)els.prompt.focus();}

  function renderStoredMessage(m){if(m.kind==='image')addImageResult(m.content,m.meta||{});else addMessage(m.role,m.content,m.kind==='error');}
  function addMessage(role,text,isError=false){
    els.welcome.classList.add('hidden');const wrap=document.createElement('article');wrap.className='message '+(role==='user'?'user':'assistant');
    const avatar=document.createElement('div');avatar.className='message-avatar';avatar.textContent=role==='user'?(state.user?.name||'U').charAt(0).toUpperCase():'C';
    const body=document.createElement('div');body.className='message-body';const content=document.createElement('div');content.className='message-content';content.innerHTML=role==='assistant'?renderMarkdown(text):nl2br(esc(text));if(isError)content.style.color='#ff8f99';body.append(content);
    const actions=document.createElement('div');actions.className='message-actions';actions.innerHTML=`<button type="button" title="کپی">${icons.copy}</button>`;$('button',actions).addEventListener('click',()=>navigator.clipboard.writeText(text).then(()=>toast('کپی شد.','success')));body.append(actions);wrap.append(avatar,body);els.messages.append(wrap);wireCodeCopy(wrap);scrollBottom();return wrap;
  }
  function addThinking(label='Cora در حال فکر کردن است...'){
    els.welcome.classList.add('hidden');const el=document.createElement('article');el.className='message assistant thinking-message';el.innerHTML=`<div class="message-avatar">C</div><div class="message-body"><div class="thinking-card"><span class="thinking-orb"></span><span class="thinking-copy"><b>${esc(label)}</b><span>در حال آماده‌سازی پاسخ <span class="thinking-dots"><i></i><i></i><i></i></span></span></span></div></div>`;els.messages.append(el);scrollBottom();return el;
  }
  function addImageLoading(prompt){
    els.welcome.classList.add('hidden');const el=document.createElement('article');el.className='message assistant image-loading-message';el.innerHTML=`<div class="message-avatar">C</div><div class="message-body"><div class="image-generation"><div class="image-stage loading"><div class="gen-loader"><div class="thinking-orb"></div><b>در حال ساخت تصویر</b><small>${esc(prompt.slice(0,80))}</small></div></div><div class="image-meta"><div><b>Image Generation</b><small>مدل در حال پردازش جزئیات است...</small></div></div></div></div>`;els.messages.append(el);scrollBottom();return el;
  }
  function addImageResult(url,meta={}){
    els.welcome.classList.add('hidden');const el=document.createElement('article');el.className='message assistant';const safeUrl=/^(data:image\/|https?:\/\/)/i.test(url)?url:'';
    el.innerHTML=`<div class="message-avatar">C</div><div class="message-body"><div class="image-generation"><div class="image-stage"><img class="generated-img" alt="تصویر ساخته‌شده توسط Cora"></div><div class="image-meta"><div><b>${esc(meta.model||state.ai?.image_model||'Cora Image')}</b><small>${esc(meta.size||'AI generated image')}</small></div><button type="button">دانلود تصویر</button></div></div></div>`;
    $('img',el).src=safeUrl;$('button',el).addEventListener('click',()=>downloadImage(safeUrl));els.messages.append(el);scrollBottom();return el;
  }
  function downloadImage(url){if(!url)return;const a=document.createElement('a');a.href=url;a.download='cora-ai-image.png';a.rel='noopener';document.body.append(a);a.click();a.remove();}

  async function sendCurrent(){
    const text=els.prompt.value.trim();if(!text||state.busy)return;if(!state.user){openAuth();toast('برای استفاده از Cora وارد حساب شوید.');return;}
    const mode=state.mode;els.prompt.value='';autoGrow();addMessage('user',text);setBusy(true);state.controller=new AbortController();
    const loader=mode==='image'?addImageLoading(text):addThinking();
    try{
      const endpoint=mode==='image'?'api/image.php':'api/chat.php';const body=mode==='image'?{prompt:text,size:els.imageSize.value,conversation_id:state.conversationId,csrf:state.csrf}:{message:text,conversation_id:state.conversationId,csrf:state.csrf};
      const r=await fetch(endpoint,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/json'},body:JSON.stringify(body),signal:state.controller.signal});const d=await r.json().catch(()=>({error:'پاسخ نامعتبر از سرور'}));loader.remove();if(!r.ok)throw Object.assign(new Error(d.error||'خطای سرویس'),{status:r.status,data:d});state.conversationId=Number(d.conversation_id);
      if(mode==='image')addImageResult(d.image,{model:d.model,size:els.imageSize.value});else addMessage('assistant',d.message);
      await loadHistory(els.historySearch.value);
    }catch(e){loader.remove();if(e.name==='AbortError'){toast('درخواست متوقف شد.');}else{addMessage('assistant',e.message,true);if(e.status===419)await refreshSession();}}
    finally{state.controller=null;setBusy(false);}
  }
  function stopRequest(){if(state.controller)state.controller.abort();}

  function renderMarkdown(src){
    const blocks=[];let text=String(src).replace(/```([\w#+.-]*)\n?([\s\S]*?)```/g,(_,lang,code)=>{const id=blocks.length;blocks.push(`<div class="code-block"><div class="code-head"><span>${esc(lang||'code')}</span><button class="copy-code" type="button">کپی کد</button></div><pre><code>${highlightCode(code,lang)}</code></pre></div>`);return `\n@@CODE${id}@@\n`;});
    text=esc(text);
    text=text.replace(/^### (.+)$/gm,'<h3>$1</h3>').replace(/^## (.+)$/gm,'<h2>$1</h2>').replace(/^# (.+)$/gm,'<h1>$1</h1>');
    text=text.replace(/^&gt; (.+)$/gm,'<blockquote>$1</blockquote>');
    text=text.replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>').replace(/__(.+?)__/g,'<strong>$1</strong>').replace(/`([^`\n]+)`/g,'<code>$1</code>');
    text=text.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g,'<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>');
    const lines=text.split('\n');let out='',inUl=false,inOl=false;
    const close=()=>{if(inUl){out+='</ul>';inUl=false;}if(inOl){out+='</ol>';inOl=false;}};
    for(let line of lines){if(/^[-*] /.test(line)){if(inOl){out+='</ol>';inOl=false;}if(!inUl){out+='<ul>';inUl=true;}out+='<li>'+line.slice(2)+'</li>';continue;}if(/^\d+\. /.test(line)){if(inUl){out+='</ul>';inUl=false;}if(!inOl){out+='<ol>';inOl=true;}out+='<li>'+line.replace(/^\d+\. /,'')+'</li>';continue;}close();if(/^@@CODE\d+@@$/.test(line.trim())){out+=line.trim();continue;}if(/^<(h[1-3]|blockquote)/.test(line)){out+=line;continue;}if(line.trim()===''){out+='';continue;}out+='<p>'+line+'</p>';}
    close();out=out.replace(/@@CODE(\d+)@@/g,(_,i)=>blocks[Number(i)]||'');return out;
  }
  function highlightCode(raw,lang=''){
    const rx=/(\/\/[^\n]*|#[^\n]*|\/\*[\s\S]*?\*\/|"(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*'|\b(?:function|class|const|let|var|if|else|for|while|return|new|try|catch|throw|async|await|import|export|from|public|private|protected|static|echo|require|include|namespace|use|fn|def|lambda|SELECT|FROM|WHERE|INSERT|UPDATE|DELETE|CREATE|TABLE|VALUES|AND|OR|NULL|true|false|null)\b|\b\d+(?:\.\d+)?\b)/g;
    let out='',last=0,m;while((m=rx.exec(raw))){out+=esc(raw.slice(last,m.index));const t=m[0];let c=/^(\/\/|#|\/\*)/.test(t)?'tok-com':/^['"]/.test(t)?'tok-str':/^\d/.test(t)?'tok-num':/^(function|def|fn)$/.test(t)?'tok-fn':'tok-key';out+=`<span class="${c}">${esc(t)}</span>`;last=m.index+t.length;}return out+esc(raw.slice(last));
  }
  function wireCodeCopy(root){$$('.copy-code',root).forEach(b=>b.addEventListener('click',()=>{const code=b.closest('.code-block').querySelector('code').innerText;navigator.clipboard.writeText(code).then(()=>{b.textContent='کپی شد';setTimeout(()=>b.textContent='کپی کد',1200);});}));}

  init();
})();
