// Get the Sidebar
var mySidebar = document.getElementById("mySidebar");

// Get the DIV with overlay effect
var overlayBg = document.getElementById("myOverlay");

// Toggle between showing and hiding the sidebar, and add overlay effect
function k8_open() {
  if (mySidebar.style.display === 'block') {
    mySidebar.style.display = 'none';
    overlayBg.style.display = "none";
  } else {
    mySidebar.style.display = 'block';
    overlayBg.style.display = "block";
  }
}

function otp(length = 8, type = "alphanumeric") {
    let chars = "";

   if (type === "numeric") {
	let chars = "0123456789";   
   }

    if (type === "alpha") {
        chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
    }
    if (type === "alphanumeric") {
        chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    }

    let result = "";
    const randomValues = new Uint32Array(length);
    crypto.getRandomValues(randomValues);

    for (let i = 0; i < length; i++) {
        result += chars[randomValues[i] % chars.length];
    }

    return result;
}



// Encrypt (accepts string or JSON string)
async function encrypt(data, password) {

    const plain = String(data);
    const enc = new TextEncoder();

    const keyMaterial = await crypto.subtle.importKey(
        "raw",
        enc.encode(password),
        "PBKDF2",
        false,
        ["deriveKey"]
    );

    const salt = crypto.getRandomValues(new Uint8Array(16));
    const iv = crypto.getRandomValues(new Uint8Array(12));

    const key = await crypto.subtle.deriveKey(
        {
            name: "PBKDF2",
            salt,
            iterations: 100000,
            hash: "SHA-256"
        },
        keyMaterial,
        {
            name: "AES-GCM",
            length: 256
        },
        false,
        ["encrypt"]
    );

    const cipher = await crypto.subtle.encrypt(
        { name: "AES-GCM", iv },
        key,
        enc.encode(plain)
    );

    return btoa(JSON.stringify({
        s: btoa(String.fromCharCode(...salt)),
        i: btoa(String.fromCharCode(...iv)),
        d: btoa(String.fromCharCode(...new Uint8Array(cipher)))
    }));
}


// Decrypt (always returns original string)
async function decrypt(cipherText, password) {

    const obj = JSON.parse(atob(cipherText));

    const salt = Uint8Array.from(atob(obj.s), c => c.charCodeAt(0));
    const iv   = Uint8Array.from(atob(obj.i), c => c.charCodeAt(0));
    const data = Uint8Array.from(atob(obj.d), c => c.charCodeAt(0));

    const enc = new TextEncoder();

    const keyMaterial = await crypto.subtle.importKey(
        "raw",
        enc.encode(password),
        "PBKDF2",
        false,
        ["deriveKey"]
    );

    const key = await crypto.subtle.deriveKey(
        {
            name: "PBKDF2",
            salt,
            iterations: 100000,
            hash: "SHA-256"
        },
        keyMaterial,
        {
            name: "AES-GCM",
            length: 256
        },
        false,
        ["decrypt"]
    );

    const plain = await crypto.subtle.decrypt(
        { name: "AES-GCM", iv },
        key,
        data
    );

    return new TextDecoder().decode(plain);
}

/*(async()=>{const U=location.hostname;try{let d=JSON.parse(await decrypt("eyJzIjoiNzViSW8xSlFybGZTTmREeDhnbnREUT09IiwiaSI6Im16UXV4eHZJY3Q1Y1JzckwiLCJkIjoiWjNOOUkxblNEVHpxUlhrSnFrQ0VlODduVS9PSjlrTDMwdis5NlZJd29UR0xpOERvYU8yeUprK09mdGcrS2dkekRucEJ6d29vaHh0NjJ5cTlmVk9rTUFJSi8zOXdFbzBTNUdYNlNIbmUzb3dIOS9XSU9KM1N3QjFkN0lIMnhSbW5MNGwwL3J4L3hUbUhjUjJZM0JCMzZCVjJMRkovM3htQXZnQXo1M3Z4TGpsMC95TXVHc0JZNDVMZ3AvZ2pxWmV5NzVhUFhFNURQcWhkQmFzSE91dTBiU3NNN25KMGVuMTZJRDZVcFNrdythN3NybUIrQnhUOFEyc2ZpNzZwNjJtdlBocHQ1WlVsQnBKTDVmT2lCb1F1dnJWZmtvanN6WGFxQUlETjVrQ1lDT3I3WnB5R2c4L2l1T3JGK0NDeC9lR01EV2F2N3pXWTVBL21HVS9WR3hoZGluQ3hvbVJoaUduandpODYzZkpXd3FXWUkwNzh4UlVlMStPSTc4azhMai9ZVlRUMlEwWFc5UWpiQjc2MnhUYU41QjFXUUlpdE5CdmU4bmVKUUdTL3V1eVpWM0tSUTZWUkx4aE5raE1oZjVyUnBUeTJLQ2VwOE5NeE53a2dDK1RqaXVKV3U2aml1bWVna1hSQTRHcz0ifQ==",U));if(d.domain!=U||Date.now()>Date.parse(d.valid_until))throw 0}catch{document.body.innerHTML="<h1 class='k8-text-red k8-padding'>License void — base code tampered or expired.</h1>"}})();*/



const formatter = new Intl.NumberFormat('en-IN', {
  style: 'currency',
  currency: 'INR',

  // These options are needed to round to whole numbers if that's what you want.
  //minimumFractionDigits: 0, // (this suffices for whole numbers, but will print 2500.10 as $2,500.1)
  //maximumFractionDigits: 0, // (causes 2500.99 to be printed as $2,501)
}); 


 /*multi curency demo format.in.format(1234567.89);*/
const format = {
  in: new Intl.NumberFormat('en-IN', {
    style: 'currency',
    currency: 'INR'
  }),

  us: new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }),

  eu: new Intl.NumberFormat('de-DE', {
    style: 'currency',
    currency: 'EUR'
  }),

  ru: new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }),

  jp: new Intl.NumberFormat('ja-JP', {
    style: 'currency',
    currency: 'JPY'
  }),

  cn: new Intl.NumberFormat('zh-CN', {
    style: 'currency',
    currency: 'CNY'
  })
};
 /*multi curency demo format.in.format(1234567.89);*/
function toggleDropdown(el) {
 let dropdown = el.nextElementSibling;
  if (dropdown) {
    dropdown.classList.toggle("k8-hide");
    console.log("Dropdown toggled!"); // Debugging log
  } else {
    console.error("Dropdown element not found!");
  }
   let icon = el.querySelector("i");
   if (dropdown.classList.contains("k8-hide")) {
      icon.classList.replace("fa-caret-down", "fa-caret-right");
    } else {
      icon.classList.replace("fa-caret-right", "fa-caret-down");
    }
  
  
}

function date(format, epoch = null, tzOffset = 330) { //PHP DATE FUNCTION IN JS WITH TZ OFFSET 330 munites = +5:30 IST
  let timestamp = (epoch !== null && epoch !== undefined) 
  ? Number(epoch) 
  : Date.now();

  // Convert seconds to milliseconds
  //if (timestamp.toString().length === 10) timestamp *= 1000;
  
  if (Math.abs(timestamp) < 1e12) timestamp *= 1000; //support 11Digit Seconds feature proof

  // Apply timezone offset (in minutes)
  // JS Date is always in local/UTC, so we shift timestamp manually
  timestamp += tzOffset * 60 * 1000;

  const d = new Date(timestamp);

  const pad = (n, c = 2) => String(n).padStart(c, '0');

  // ISO week number
  const getISOWeek = (date) => {
    const target = new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate()));
    const dayNr = (target.getUTCDay() + 6) % 7;
    target.setUTCDate(target.getUTCDate() - dayNr + 3);
    const firstThursday = new Date(Date.UTC(target.getUTCFullYear(), 0, 4));
    return 1 + Math.round((target - firstThursday) / (7 * 86400000));
  };

  // ISO year
  const getISOYear = (date) => {
    const week = getISOWeek(date);
    let year = date.getUTCFullYear();
    if (week === 1 && date.getUTCMonth() === 11) year += 1;
    if (week >= 52 && date.getUTCMonth() === 0) year -= 1;
    return year;
  };

  const map = {
    // Day
    d: pad(d.getUTCDate()),
    D: ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][d.getUTCDay()],
    j: d.getUTCDate(),
    l: ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][d.getUTCDay()],
    N: d.getUTCDay() === 0 ? 7 : d.getUTCDay(),
    S: (() => {
      const day = d.getUTCDate();
      if (day % 10 === 1 && day !== 11) return 'st';
      if (day % 10 === 2 && day !== 12) return 'nd';
      if (day % 10 === 3 && day !== 13) return 'rd';
      return 'th';
    })(),
    w: d.getUTCDay(),
    z: Math.floor((d - Date.UTC(d.getUTCFullYear(), 0, 0)) / 86400000),

    // Week
    W: getISOWeek(d),

    // Month
    F: ['January','February','March','April','May','June','July','August','September','October','November','December'][d.getUTCMonth()],
    m: pad(d.getUTCMonth() + 1),
    M: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][d.getUTCMonth()],
    n: d.getUTCMonth() + 1,
    t: new Date(Date.UTC(d.getUTCFullYear(), d.getUTCMonth() + 1, 0)).getUTCDate(),

    // Year
    L: ((d.getUTCFullYear() % 4 === 0 && d.getUTCFullYear() % 100 !== 0) || (d.getUTCFullYear() % 400 === 0)) ? 1 : 0,
    o: getISOYear(d),
    Y: d.getUTCFullYear(),
    y: String(d.getUTCFullYear()).slice(-2),

    // Time
    a: d.getUTCHours() < 12 ? 'am' : 'pm',
    A: d.getUTCHours() < 12 ? 'AM' : 'PM',
    g: d.getUTCHours() % 12 || 12,
    G: d.getUTCHours(),
    h: pad(d.getUTCHours() % 12 || 12),
    H: pad(d.getUTCHours()),
    i: pad(d.getUTCMinutes()),
    s: pad(d.getUTCSeconds()),
    u: pad(d.getUTCMilliseconds() * 1000, 6),
  };

  return format.replace(/\\?([a-zA-Z])/g, (_, char) =>
    map[char] !== undefined ? map[char] : char
  );
}
/*auto modal*/
(function ensureModal() {
  function addModal() {
    if (document.getElementById('modal')) return;

    const html = `<div  class="k8-blur"></div>
<div id="modal" class="k8-popup k8-white">
  <div class="head k8-indigo k8-padding">
    <div class="title">Dynamic Window</div>
    <button class="k8-btn k8-red" onclick="closeModal(this);">
      <i class="fa fa-xmark"></i>
    </button>
  </div>

  <div class="body k8-white k8-border k8-custom-border">
    <i class="fa fa-spinner fa-spin fa-4x"></i>
  </div>
</div>`;

    document.body.insertAdjacentHTML('beforeend', html);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', addModal);
  } else {
    addModal();
  }
})();


/*auto modal*/
//dynamyc form handeler

function form_handel(id="", trigger="", callback=null, url="./", success="✅ Table saved successfully!", failed="❌ Failed to save table!") {

    if (!id || !trigger) {
        console.warn("form_handel(): Required parameters missing.");
        return;
    }

    const form = document.getElementById(id);

    if (!form) {
        console.warn("form_handel(): Form not found:", id);
        return;
    }

    const btn = form.querySelector("button[type='submit']");

    if (!btn) {
        console.warn("form_handel(): Submit button not found.");
        return;
    }

    let notice = document.querySelector('#modal #notice');

    success = "<span class='k8-text-green k8-strong'>" + success + "</span>";
    failed = "<span class='k8-text-red k8-strong'>" + failed + "</span>";

    // Toggle loading spinner.
    function setLoading(loading) {

        if (loading) {
            btn.disabled = true;

            if (!btn.querySelector('.fa-spinner')) {
                const spinner = document.createElement('i');
                spinner.className = 'fa fa-spinner fa-spin';
                spinner.setAttribute('aria-hidden', 'true');
                spinner.style.marginLeft = '8px';

                btn.appendChild(spinner);
            }

        } else {
            btn.querySelectorAll('.fa-spinner').forEach(spinner => {
                spinner.remove();
            });

            btn.disabled = false;
        }
    }

    form.addEventListener("submit", async function(e) {

        e.preventDefault();

        if (!form.reportValidity()) {
            return;
        }

        if (!notice) {
            notice = document.createElement('div');
            notice.id = "notice";
            notice.className = 'k8-small k8-strong k8-padding-small';
            notice.style.width = '100%';

            btn.insertAdjacentElement('beforebegin', notice);
        }

        const formData = new FormData(form);
        formData.append(trigger, '1');

        setLoading(true);

        try {

            const response = await fetch(url, {
                method: "POST",
                body: formData
            });

            const result = await response.text();

            notice.innerHTML = "";

            if (result.trim() === "200") {

                notice.innerHTML = success;

                // Transform submit button into Close button.
                btn.type = "button";

                btn.classList.remove(
                    "k8-green",
                    "k8-red",
                    "k8-blue",
                    "k8-orange"
                );

                btn.classList.add("k8-blue-grey");

                btn.innerHTML = "<i class='fa fa-xmark'></i> Close Window";

                if (callback && typeof callback === "function") {
                    await callback();
                }

                btn.onclick = function() {
                    closeModal(btn);
                };

            } else {
                notice.innerHTML = failed;
            }

        } catch (error) {

            console.error("Form submission error:", error);
            notice.innerHTML = failed;

        } finally {

            // Remove spinner and re-enable button.
            setLoading(false);

        }
    });
}


function closeModal(el) {
  const modal = el.closest('#modal, .k8-popup');
  if (!modal) return;
   v_blur();
 document.querySelector('#modal .body').innerHTML=` <span class="xloader">L &nbsp; ading</span>`;
  modal.style.display = 'none';
}

function epoachjs(epochTime){
	const date = new Date(epochTime * 1000); // Convert seconds to milliseconds

        // Extract date components
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0'); // Month is 0-indexed
        const day = String(date.getDate()).padStart(2, '0');

        // Extract time components
        let hours = date.getHours();
        const minutes = String(date.getMinutes()).padStart(2, '0');

        // Determine AM or PM
        const ampm = hours >= 12 ? 'PM' : 'AM';

        // Convert to 12-hour format
        //hours = hours % 12 || 12; // Convert 0 to 12 for midnight

        // Format as "YYYY-MM-DD HH:mm AM/PM"
        return `${day}-${month}-${year}, ${hours}:${minutes} ${ampm}`;

}

function selectElement(id, valueToSelect) {    
    let element = document.getElementById(id);
    element.value = valueToSelect;
}
// Close the sidebar with the close button
function k8_close() {
  mySidebar.style.display = "none";
  overlayBg.style.display = "none";
}

function v_blur(){
const blr=document.getElementsByClassName('k8-blur');
for (let i = 0; i < blr.length; i++) { 
if(blr[i].style.display==="block"){
	blr[i].style.display="none";
}else{
blr[i].style.display="block";
}}}
 function openInNewTab(imageElement) {
	 //console.log(imageElement.src);
      window.open(imageElement.src, '_blank');
    }
function wclose(el){
	el.parentNode.parentNode.remove();
}

function cfm(el){
	var qs=el.getAttribute('data-q');
	var atrue=el.getAttribute('data-true');
	var afalse=el.getAttribute('data-false');
	var dp='"none"';
	var html=qs +"<a href='"+atrue+"' class='k8-btn k8-green' style='margin-left:8px;'>YES</a><a href='"+afalse+"' style='margin-left:8px;' onclick='this.parentNode.parentNode.parentNode.parentNode.parentNode.style.display="+dp+";' class='k8-btn k8-red'>NO</a>";
	
	
	document.getElementsByClassName("ddata")[0].innerHTML=html;
	document.getElementsByClassName("tnot")[0].style.display="block";
	
}
function pview(el){ /*PASSWORD VIEW*/
if(el.previousElementSibling.type==="password"){
	el.previousElementSibling.type="text";
	el.children[0].classList.remove("fa-eye");
	el.children[0].classList.add("fa-eye-slash");
}else{
	el.previousElementSibling.type="password";
	el.children[0].classList.remove("fa-eye-slash");
	el.children[0].classList.add("fa-eye");
}

}
var confirmTimer = null; // global timer holder

function confirm_action(el){
    var qs     = el.getAttribute('data-qs');
    var atrue  = el.getAttribute('data-true');
    var afalse = el.getAttribute('data-false');

    var html = `${qs}<a href='${atrue}' class='k8-btn k8-green' style='margin-left:8px;'>YES</a>
        <a href='${afalse}' style='margin-left:8px;' class='k8-btn k8-red' onclick='this.parentNode.parentNode.parentNode.parentNode.parentNode.style.display="none";'>NO</a>`;

    var box = document.getElementsByClassName("tnot")[0];
    document.getElementsByClassName("ddata")[0].innerHTML = html;
    box.style.display = "block";

    // 🔥 CLEAR OLD TIMER
    if (confirmTimer) {
        clearTimeout(confirmTimer);
    }

    // ⏳ START NEW TIMER
    confirmTimer = setTimeout(function(){
        box.style.display = "none";
    }, 10000);
}



window.addEventListener('load', () => {
  const loader = document.querySelector('.k8-loader');
  if (!loader) return;

  loader.style.transition = 'opacity 0.4s ease';

  setTimeout(() => {
    loader.style.opacity = '0';
    setTimeout(() => loader.style.display = 'none', 400);
  }, 1000);
});
	
$(document).ready(function() { 
   setTimeout(function() { 
    $('.autoHide').hide(); 
  }, 10000);	
  
});

function makeDragable(dragHandle) {

  let dragObj = null;
  let xOffset = 0;
  let yOffset = 0;

  const handles = document.querySelectorAll(dragHandle);

  handles.forEach(handle => {
    const target = handle.closest("#modal");
    target.style.zIndex = target.style.zIndex || "200";

    handle.addEventListener("mousedown", startDrag);
    handle.addEventListener("touchstart", startDrag, { passive: false });

    function startDrag(e) {
      e.preventDefault();
      e.stopPropagation();

      dragObj = target;
      dragObj.style.position = "fixed";
      dragObj.style.transform = "none";

      const rect = dragObj.getBoundingClientRect();

      if (e.type === "mousedown") {
        xOffset = e.clientX - rect.left;
        yOffset = e.clientY - rect.top;
        dragObj.style.zIndex = (+dragObj.style.zIndex || 200) + 1;

        window.addEventListener("mousemove", dragMove);
        window.addEventListener("mouseup", stopDrag);
      } else {
        xOffset = e.touches[0].clientX - rect.left;
        yOffset = e.touches[0].clientY - rect.top;
        dragObj.style.zIndex = (+dragObj.style.zIndex || 200) + 1;

        window.addEventListener("touchmove", dragMove, { passive: false });
        window.addEventListener("touchend", stopDrag);
      }
    }
  });

  function dragMove(e) {
    if (!dragObj) return;

    e.preventDefault();

    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;

    const maxX = window.innerWidth - dragObj.offsetWidth;
    const maxY = window.innerHeight - dragObj.offsetHeight;

    dragObj.style.left = Math.max(0, Math.min(clientX - xOffset, maxX)) + "px";
    dragObj.style.top  = Math.max(0, Math.min(clientY - yOffset, maxY)) + "px";
  }

  function stopDrag() {
    dragObj = null;
    window.removeEventListener("mousemove", dragMove);
    window.removeEventListener("mouseup", stopDrag);
    window.removeEventListener("touchmove", dragMove);
    window.removeEventListener("touchend", stopDrag);
  }
}




function show(formId) {
    document.getElementById(formId).style.display = 'block';
	
}

function hide(formId) {
    document.getElementById(formId).style.display = 'none';
}

function showimg(el){
document.getElementsByClassName("k8-box")[0].style.display="block";	
document.getElementsByClassName("k8-box")[0].querySelector(".wbody").style="text-align:center;";
document.getElementsByClassName("k8-box")[0].querySelector(".wbody").innerHTML="<img src='data:image/jpeg;base64,"+ el.value+"' style='width:100%;height:100%;border:1px solid #eee;object-fit: contain;'/>";
}

function addwin(el){
document.getElementsByClassName("k8-box")[0].style.display="block";	
document.getElementsByClassName("k8-box")[0].querySelector(".whead").querySelector("SPAN").innerHTML=el.getAttribute('data-title');
document.getElementsByClassName("k8-box")[0].querySelector(".wbody").style="text-align:center;";
document.getElementsByClassName("k8-box")[0].querySelector(".wbody").innerHTML=el.getAttribute('data-html');
}

function userform(el){
document.getElementsByClassName("k8-box")[0].style.display="block";	
document.getElementsByClassName("k8-box")[0].querySelector(".whead").querySelector("SPAN").innerHTML='EDIT USER -'+el.getAttribute('data-uid');
document.getElementsByClassName("k8-box")[0].querySelector(".wbody").style="text-align:center;";
var html;
html=''+atob(el.getAttribute('data-addr'))+el.getAttribute('data-fname')+el.getAttribute('data-uname')+atob(el.getAttribute('data-gid'))+el.getAttribute('data-type')+el.getAttribute('data-verify');
document.getElementsByClassName("k8-box")[0].querySelector(".wbody").innerHTML=html;
}

//SEARCH TABLE TABLE DIVS
function searchTableDivs(input) {
    var filter, found, containers, rows, cols, i, j;
    filter = input.value.toUpperCase(); // convert input to uppercase
    var inputs = document.getElementsByClassName("filter");
    var index = Array.from(inputs).indexOf(input);
    containers = document.getElementsByClassName("sctable"); // container for div "table"

    if(!containers[index]){
        index = 0;    
    }

    rows = containers[index].getElementsByClassName("k8-row");

    for (i = 0; i < rows.length; i++) {
        cols = rows[i].getElementsByClassName("k8-col");
        found = false;
        for (j = 0; j < cols.length; j++) {
            if(cols[j].textContent.toUpperCase().indexOf(filter) > -1){
                found = true;
                break;
            }
        }
        rows[i].style.display = found ? "" : "none";
    }
}
document.addEventListener("click", function(e){
  const openBtn = e.target.closest("[data-k8-modal]");
  if(openBtn){
    const target = document.querySelector(openBtn.dataset.k8Modal);
    if(target) target.style.display = "block";
  }

  const closeBtn = e.target.closest("[data-k8-close]");
  if(closeBtn){
    closeBtn.closest(".k8-modal, .k8-popup").style.display = "none";
  }
});
function k8Tabs(container){
  const tabs = container.querySelectorAll("[data-k8-tab]");
  const panels = container.querySelectorAll("[data-k8-panel]");

  tabs.forEach(tab=>{
    tab.addEventListener("click", ()=>{
      const target = tab.dataset.k8Tab;

      tabs.forEach(t=>t.classList.remove("k8-theme"));
      panels.forEach(p=>p.style.display="none");

      tab.classList.add("k8-theme");
      container.querySelector(target).style.display="block";
    });
  });
}

document.querySelectorAll(".k8-tabs").forEach(k8Tabs);
document.addEventListener("click", e=>{
  const head = e.target.closest(".k8-acc-header");
  if(!head) return;

  const item = head.parentElement;
  item.classList.toggle("active");
});
function k8Toast(msg,type="theme",time=3000){
  const box = document.createElement("div");
  box.className = `k8-toast k8-${type}`;
  box.innerHTML = msg;

  document.body.appendChild(box);

  setTimeout(()=>box.remove(), time);
}
function k8Drawer(id){
  const el = document.querySelector(id);
  el.classList.toggle("active");
}
document.addEventListener("mouseover", e=>{
  const el = e.target.closest("[data-k8-tip]");
  if(!el) return;

  const tip = document.createElement("div");
  tip.className = "k8-tooltip-text";
  tip.innerText = el.dataset.k8Tip;
  el.appendChild(tip);
});

function searchTable(input) {
    var  filter, found, table, tr, td, i, j;
    filter = input.value.toUpperCase();
	var inputs = document.getElementsByClassName("filter");
	index = Array.from(inputs).indexOf(input);
	 table = document.getElementsByClassName("sctable");
	 
	 
	if(!table[index]){
	index = 0;	    
	}
		
    tr = table[index].getElementsByTagName("tr");
	
    for (i = 0; i < tr.length; i++) {
        td = tr[i].getElementsByTagName("td");
        for (j = 0; j < td.length; j++) {
            if (td[j].innerHTML.toUpperCase().indexOf(filter) > -1) {
                found = true;
            }
        }
        if (found) {
            tr[i].style.display = "";
            found = false;
        } else {
            tr[i].style.display = "none";
        }
    }
}
function searchTableMulti(input = null) {

    var tableList = document.getElementsByClassName("sctable");
    var inputs = document.getElementsByClassName("filter");

    // detect index (same as your old logic)
    let index = 0;
    if (input) {
        index = Array.from(inputs).indexOf(input);
        if (index < 0) index = 0;
    }

    var table = tableList[index] || tableList[0];
    var tr = table.getElementsByTagName("tr");

    // 🔥 collect ALL active filters
    var activeFilters = [];

    for (let f of inputs) {
        if (f.value !== "") {
            activeFilters.push(f.value.toUpperCase());
        }
    }

    // loop rows
    for (let i = 0; i < tr.length; i++) {

        let td = tr[i].getElementsByTagName("td");
        let rowMatch = true;

        // 🔥 check ALL filters (AND condition)
        for (let filter of activeFilters) {

            let found = false;

            for (let j = 0; j < td.length; j++) {
                if (td[j].innerHTML.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }

            if (!found) {
                rowMatch = false;
                break;
            }
        }

        tr[i].style.display = rowMatch ? "" : "none";
    }
}

function setfilter(el){
document.getElementById("filter").value=el.id;	
searchTable();
}

// 🔥 COMPLETE ICON PICKER (FINAL)

let FA_CACHE = null;
let ICON_TARGET = null;
let ICON_PREVIEW = null;

// 👉 ensure modal exists
(function createIconModal(){
  if (document.getElementById("icon_picker_modal")) return;

  const modal = document.createElement("div");
  modal.id = "icon_picker_modal";
  modal.style = "display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;";

  modal.innerHTML = `
    <div style="background:#fff;width:700px;margin:50px auto;padding:15px;border-radius:8px;">
      <h3>Select Icon</h3>
      <input type="text" id="icon_search" class="k8-input" placeholder="Search icon...">
      <div id="icon_list" style="
        display:grid;
        grid-template-columns:repeat(auto-fill,minmax(80px,1fr));
        gap:10px;
        max-height:400px;
        overflow:auto;
        margin-top:10px;
      "></div>
      <br>
      <button onclick="close_icon_picker()">Close</button>
    </div>
  `;

  document.body.appendChild(modal);
})();

// 👉 load icons
async function loadFAIcons() {
  if (FA_CACHE) return FA_CACHE;

  const res = await fetch('./engine/metadata/icons.json');
  const json = await res.json();

  const icons = [];

  Object.entries(json).forEach(([name, meta]) => {
    (meta.styles || []).forEach(style => {
      if (['solid','regular','brands'].includes(style)) {
        icons.push({
          name,
          class: `fa-${style} fa-${name}`
        });
      }
    });
  });

  FA_CACHE = icons;
  return icons;
}

// 👉 open picker
async function open_icon_picker(inputId, previewId = null){

  ICON_TARGET = document.getElementById(inputId);
  ICON_PREVIEW = previewId ? document.getElementById(previewId) : null;

  if (!ICON_TARGET) return;

  const modal = document.getElementById("icon_picker_modal");
  modal.style.display = "block";

  const icons = await loadFAIcons();
  const list = document.getElementById("icon_list");

  function render(filter=""){
    list.innerHTML = "";

    const filtered = icons.filter(i => i.name.includes(filter));

    // 🔥 limit render (critical for performance)
    filtered.slice(0, 500).forEach(icon => {

      const div = document.createElement("div");
      div.innerHTML = `
        <i class="${icon.class} fa-2x"></i>
        <div style="font-size:10px">${icon.name}</div>
      `;
      div.style.cursor = "pointer";

      div.onclick = () => {
        ICON_TARGET.value = icon.class;
        ICON_TARGET.dispatchEvent(new Event("input"));

        if (ICON_PREVIEW) {
          ICON_PREVIEW.innerHTML = `<i class="${icon.class}"></i>`;
        }

        close_icon_picker();
      };

      list.appendChild(div);
    });
  }

  render();

  const search = document.getElementById("icon_search");
  search.value = "";

  search.oninput = (e)=>{
    render(e.target.value.toLowerCase());
  };
}

// 👉 close picker
function close_icon_picker(){
  document.getElementById("icon_picker_modal").style.display = "none";
}
async function getExifOrientation(file) {

    const buffer = await file.arrayBuffer();
    const view = new DataView(buffer);

    // JPEG?
    if (view.getUint16(0, false) !== 0xFFD8) {
        return 1;
    }

    let offset = 2;

    while (offset < view.byteLength) {

        const marker = view.getUint16(offset, false);
        offset += 2;

        if (marker === 0xFFE1) {

            const length = view.getUint16(offset, false);

            // "Exif"
            if (view.getUint32(offset + 2, false) !== 0x45786966) {
                break;
            }

            const tiff = offset + 8;

            const little =
                view.getUint16(tiff, false) === 0x4949;

            const firstIFD =
                view.getUint32(tiff + 4, little);

            let dir = tiff + firstIFD;

            const entries =
                view.getUint16(dir, little);

            dir += 2;

            for (let i = 0; i < entries; i++) {

                const entry = dir + i * 12;

                const tag =
                    view.getUint16(entry, little);

                if (tag === 0x0112) {

                    return view.getUint16(
                        entry + 8,
                        little
                    );
                }
            }

            break;

        } else if ((marker & 0xFF00) !== 0xFF00) {

            break;

        } else {

            offset += view.getUint16(offset, false);

        }
    }

    return 1;

}

function initSearchSelect(root = document) {

    root.querySelectorAll("select.search_select").forEach(select => {

        if (select.dataset.ssInit) return;
        select.dataset.ssInit = "1";

        const css = getComputedStyle(select);

        // Wrapper
        const wrap = document.createElement("div");
        wrap.style.cssText = `
            position:relative;
            display:inline-block;
            width:${css.width};
            min-width:${css.minWidth};
            max-width:${css.maxWidth};
            box-sizing:${css.boxSizing};
        `;

        // Input
        const input = document.createElement("input");
        input.type = "text";
        input.placeholder = "Search...";
        input.value = select.options[select.selectedIndex]?.text || "";
        input.className = select.className.replace("search_select", "");

        input.style.cssText += `
            width:100%;
            box-sizing:border-box;
        `;

        // Dropdown
        const list = document.createElement("div");
        list.style.cssText = `
            position:absolute;
            left:0;
            right:0;
            top:100%;
            background:#fff;
            border:1px solid #ccc;
            border-top:none;
            max-height:220px;
            overflow-y:auto;
            display:none;
            z-index:999999;
            box-sizing:border-box;
            box-shadow:0 3px 8px rgba(0,0,0,.15);
        `;

        function build(filter = "") {

            list.innerHTML = "";

            [...select.options].forEach(opt => {

                if (!opt.text.toLowerCase().includes(filter.toLowerCase()))
                    return;

                const item = document.createElement("div");

                item.textContent = opt.text;

                item.style.cssText = `
                    padding:8px 12px;
                    cursor:pointer;
                    white-space:nowrap;
                `;

                item.onmouseenter = () => item.style.background = "#f5f5f5";
                item.onmouseleave = () => item.style.background = "";

                item.onclick = () => {

                    input.value = opt.text;
                    select.value = opt.value;

                    select.dispatchEvent(new Event("change", {
                        bubbles: true
                    }));

                    list.style.display = "none";
                };

                list.appendChild(item);

            });

            if (!list.children.length) {

                const empty = document.createElement("div");
                empty.textContent = "No results";
                empty.style.cssText = `
                    padding:8px 12px;
                    color:#888;
                `;

                list.appendChild(empty);

            }

        }

        build();

        input.addEventListener("focus", () => {
            build("");
            list.style.display = "block";
        });

        input.addEventListener("click", () => {
            build("");
            list.style.display = "block";
        });

        input.addEventListener("input", () => {
            build(input.value);
            list.style.display = "block";
        });

        document.addEventListener("click", e => {
            if (!wrap.contains(e.target))
                list.style.display = "none";
        });

        // Sync if select changes elsewhere
        select.addEventListener("change", () => {
            input.value = select.options[select.selectedIndex]?.text || "";
        });

        wrap.appendChild(input);
        wrap.appendChild(list);

        select.parentNode.insertBefore(wrap, select.nextSibling);

        // Hide original select AFTER measuring it
        select.style.display = "none";

    });

}

// Initial
document.addEventListener("DOMContentLoaded", () => {
    initSearchSelect();
});

// Dynamic content
new MutationObserver(mutations => {

    mutations.forEach(mutation => {

        mutation.addedNodes.forEach(node => {

            if (node.nodeType !== 1) return;

            if (node.matches?.("select.search_select")) {
                initSearchSelect(node.parentNode);
            } else {
                initSearchSelect(node);
            }

        });

    });

}).observe(document.body, {
    childList: true,
    subtree: true
});




/*
 * K8 Custom Date / Time Picker
 * Replaces native date/time controls with a custom popup.
 * Supports existing .k8-date / .k8-time classes and rm_date / rm_time names.
 *
 * Saved formats:
 *   date     -> YYYY-MM-DD
 *   time     -> HH:mm (24-hour)
 *   datetime -> YYYY-MM-DDTHH:mm
 */
(function () {
    'use strict';

    // Prevent duplicate global listeners if this file is included more than once.
    if (window.k8picker && window.k8picker.__k8picker) return;

    let activeInput = null;
    let popup = null;

    let selectedDate = startOfDay(new Date());
    let viewDate = new Date(
        selectedDate.getFullYear(),
        selectedDate.getMonth(),
        1
    );

    let selectedHour = 0;
    let selectedMinute = 0;
    let currentType = 'date';

    let observerStarted = false;

    const pad = value => String(value).padStart(2, '0');

    const AUTO_SELECTOR = [
        'input[type="date"]',
        'input[type="time"]',
        'input[type="datetime-local"]',
        'input.k8-date',
        'input.k8-time',
        'input[name="rm_date"]',
        'input[name="rm_time"]'
    ].join(',');

    // --------------------------------------------------
    // DATE HELPERS
    // --------------------------------------------------

    function startOfDay(date) {
        return new Date(
            date.getFullYear(),
            date.getMonth(),
            date.getDate()
        );
    }

    function validDate(year, month, day) {
        const date = new Date(year, month - 1, day);

        return (
            date.getFullYear() === year &&
            date.getMonth() === month - 1 &&
            date.getDate() === day
        ) ? date : null;
    }

    function parseDateValue(value) {
        const text = String(value || '').trim();

        if (!text) return null;

        // YYYY-MM-DD or YYYY-MM-DDTHH:mm
        let match = text.match(/^(\d{4})-(\d{2})-(\d{2})/);

        if (match) {
            return validDate(
                Number(match[1]),
                Number(match[2]),
                Number(match[3])
            );
        }

        // DD-MM-YYYY
        match = text.match(/^(\d{2})-(\d{2})-(\d{4})/);

        if (match) {
            return validDate(
                Number(match[3]),
                Number(match[2]),
                Number(match[1])
            );
        }

        // DD/MM/YYYY
        match = text.match(/^(\d{2})\/(\d{2})\/(\d{4})/);

        if (match) {
            return validDate(
                Number(match[3]),
                Number(match[2]),
                Number(match[1])
            );
        }

        const parsed = new Date(text);

        return Number.isNaN(parsed.getTime())
            ? null
            : startOfDay(parsed);
    }

    function dateBound(input, attribute) {
        const value = input.getAttribute(attribute);
        return value ? parseDateValue(value) : null;
    }

    function parseTimeMinutes(value) {
        const parsed = parseTimeValue(value);
        if (!String(value || '').trim()) return null;
        return (parsed.hour * 60) + parsed.minute;
    }

    function timeBounds(input) {
        if (!input) return { min: null, max: null };

        let min = null;
        let max = null;

        const minValue = input.getAttribute('min');
        const maxValue = input.getAttribute('max');

        if (currentType === 'time') {
            min = parseTimeMinutes(minValue);
            max = parseTimeMinutes(maxValue);
        } else if (currentType === 'datetime') {
            const minDate = parseDateValue(minValue);
            const maxDate = parseDateValue(maxValue);
            const selectedDay = startOfDay(selectedDate).getTime();

            if (minDate && selectedDay === minDate.getTime()) {
                min = parseTimeMinutes(minValue);
            }

            if (maxDate && selectedDay === maxDate.getTime()) {
                max = parseTimeMinutes(maxValue);
            }
        }

        return { min, max };
    }

    function clampTime(input) {
        const bounds = timeBounds(input);
        let value = (selectedHour * 60) + selectedMinute;

        if (bounds.min !== null && value < bounds.min) value = bounds.min;
        if (bounds.max !== null && value > bounds.max) value = bounds.max;

        value = Math.max(0, Math.min(1439, value));
        selectedHour = Math.floor(value / 60);
        selectedMinute = value % 60;
    }

    function timeOptionDisabled(input, hour, minute) {
        const bounds = timeBounds(input);
        const value = (hour * 60) + minute;
        return (bounds.min !== null && value < bounds.min) ||
               (bounds.max !== null && value > bounds.max);
    }

    function hasValidTimeRange(input) {
        const bounds = timeBounds(input);
        return bounds.min === null || bounds.max === null || bounds.min <= bounds.max;
    }

    function periodHasAvailableTime(input, period) {
        for (let hour = 0; hour < 12; hour++) {
            const hour24 = hour + (period === 1 ? 12 : 0);
            for (let minute = 0; minute < 60; minute++) {
                if (!timeOptionDisabled(input, hour24, minute)) return true;
            }
        }
        return false;
    }

    // --------------------------------------------------
    // INPUT TYPE DETECTION
    // --------------------------------------------------

    function getInputType(input) {
        const explicit = String(
            input.dataset.type || ''
        ).toLowerCase();

        if (['date', 'time', 'datetime'].includes(explicit)) {
            return explicit;
        }

        const original = String(
            input.dataset.k8pickerOriginalType ||
            input.type ||
            ''
        ).toLowerCase();

        if (original === 'datetime-local') {
            return 'datetime';
        }

        if (
            original === 'time' ||
            input.classList.contains('k8-time') ||
            input.name === 'rm_time'
        ) {
            return 'time';
        }

        if (
            original === 'date' ||
            input.classList.contains('k8-date') ||
            input.name === 'rm_date'
        ) {
            return 'date';
        }

        return 'date';
    }

    // --------------------------------------------------
    // CSS
    // --------------------------------------------------

    function injectCSS() {
        if (document.getElementById('k8picker-css')) return;

        const style = document.createElement('style');

        style.id = 'k8picker-css';

        style.textContent = `
            .k8p-popup {
                position: fixed;
                z-index: 2147483000;
                width: 300px;
                max-width: calc(100vw - 20px);
                max-height: calc(100vh - 20px);
                overflow: auto;
                padding: 14px;
                background: #fff;
                color: #222;
                border: 1px solid #ddd;
                border-radius: 12px;
                box-shadow: 0 8px 30px rgba(0,0,0,.20);
                font-family: Arial, sans-serif;
                font-size: 14px;
                line-height: 1.35;
                box-sizing: border-box;
            }

            .k8p-popup *,
            .k8p-popup *::before,
            .k8p-popup *::after {
                box-sizing: border-box;
            }

            .k8p-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
                margin-bottom: 12px;
            }

            .k8p-title {
                font-weight: 700;
                font-size: 15px;
            }

            .k8p-nav {
                display: flex;
                gap: 5px;
            }

            .k8p-btn {
                appearance: none;
                -webkit-appearance: none;
                border: 0;
                background: #f1f1f1;
                color: #222;
                border-radius: 7px;
                padding: 8px 11px;
                cursor: pointer;
                font: inherit;
                line-height: 1.2;
            }

            .k8p-btn:hover {
                background: #e5e5e5;
            }

            .k8p-btn:disabled {
                opacity: .35;
                cursor: not-allowed;
            }

            .k8p-calendar {
                display: grid;
                grid-template-columns: repeat(7, minmax(0, 1fr));
                gap: 4px;
                text-align: center;
            }

            .k8p-weekday {
                padding: 7px 0;
                color: #777;
                font-size: 11px;
                font-weight: 700;
            }

            .k8p-day {
                width: 100%;
                border: 0;
                border-radius: 7px;
                background: transparent;
                color: #222;
                padding: 9px 0;
                cursor: pointer;
                font: inherit;
            }

            .k8p-day:hover:not(:disabled) {
                background: #eee;
            }

            .k8p-day.selected {
                background: #f4511e;
                color: #fff;
            }

            .k8p-day.today {
                outline: 1px solid #f4511e;
            }

            .k8p-day:disabled {
                opacity: .25;
                cursor: not-allowed;
            }

            .k8p-day.empty {
                pointer-events: none;
            }

            .k8p-time {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                margin: 15px 0;
            }

            .k8p-time select {
                min-width: 65px;
                padding: 10px 7px;
                border: 1px solid #ddd;
                border-radius: 7px;
                background: #fff;
                color: #222;
                font: inherit;
                cursor: pointer;
            }

            .k8p-footer {
                display: flex;
                gap: 8px;
                margin-top: 14px;
                padding-top: 10px;
                border-top: 1px solid #eee;
            }

            .k8p-footer button {
                flex: 1;
            }

            .k8p-done {
                background: #f4511e;
                color: #fff;
            }

            .k8p-done:hover {
                background: #d94113;
            }

            .k8p-today {
                color: #f4511e;
            }

            @media (max-width: 360px) {
                .k8p-popup {
                    width: calc(100vw - 20px);
                    padding: 10px;
                }

                .k8p-day {
                    padding: 8px 0;
                }

                .k8p-time {
                    gap: 5px;
                }

                .k8p-time select {
                    min-width: 0;
                    flex: 1;
                }
            }
        `;

        document.head.appendChild(style);
    }

    // --------------------------------------------------
    // PREPARE INPUT
    // --------------------------------------------------

    function prepareInput(input) {
        if (!(input instanceof HTMLInputElement)) return false;

        // Preserve original attributes before changing native input types.
        if (input.dataset.k8pickerOriginalReadonly === undefined) {
            input.dataset.k8pickerOriginalReadonly =
                input.hasAttribute('readonly') ? '1' : '0';
        }

        const previousType = String(
            input.dataset.k8pickerOriginalType || input.type || 'text'
        ).toLowerCase();

        if (!input.dataset.k8pickerOriginalType) {
            input.dataset.k8pickerOriginalType = previousType;
        }

        // Detect the intended picker type before converting native controls.
        input.dataset.type = getInputType(input);

        // Convert native date/time controls to text, including readonly fields.
        if (['date', 'time', 'datetime-local'].includes(input.type)) {
            const oldValue = input.value;
            input.type = 'text';
            input.value = oldValue;
        }

        input.setAttribute('autocomplete', 'off');
        input.classList.add('k8p-input-ready');

        // A readonly field is text-only: no popup, click handler, or focus handler.
        if (input.dataset.k8pickerOriginalReadonly === '1') {
            input.readOnly = true;
            input.removeAttribute('aria-haspopup');
            input.removeAttribute('aria-expanded');
            input.dataset.k8pickerAttached = 'readonly';
            return true;
        }

        if (!input.placeholder.trim()) {
            const placeholders = {
                date: 'Select date',
                time: 'Select time',
                datetime: 'Select date & time'
            };
            input.placeholder = placeholders[input.dataset.type] || 'Select date';
        }

        // The custom picker, not the keyboard, writes the value.
        input.readOnly = true;
        input.setAttribute('aria-haspopup', 'dialog');
        input.setAttribute('aria-expanded', 'false');

        if (input.dataset.k8pickerAttached === '1') return true;

        input.dataset.k8pickerAttached = '1';

        input.addEventListener('click', function (event) {
            event.stopPropagation();
            openPicker(input);
        });

        input.addEventListener('focus', function () {
            openPicker(input);
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openPicker(input);
            }
            if (event.key === 'Escape') closePicker();
        });

        return true;
    }

    // --------------------------------------------------
    // TIME PARSING
    // --------------------------------------------------

    function parseTimeValue(value) {
        let text = String(value || '').trim();

        // datetime-local values are formatted as YYYY-MM-DDTHH:mm.
        if (text.includes('T')) {
            text = text.split('T').pop();
        }

        if (!text) {
            return {
                hour: 0,
                minute: 0
            };
        }

        const match = text.match(
            /^(\d{1,2}):(\d{2})(?::\d{2})?\s*(AM|PM)?$/i
        );

        if (!match) {
            return {
                hour: 0,
                minute: 0
            };
        }

        let hour = Number(match[1]);

        const minute = Math.min(
            59,
            Math.max(0, Number(match[2]))
        );

        if (match[3]) {
            hour = Math.max(1, Math.min(12, hour)) % 12;

            if (match[3].toUpperCase() === 'PM') {
                hour += 12;
            }
        } else {
            hour = Math.min(23, Math.max(0, hour));
        }

        return {
            hour,
            minute
        };
    }

    // --------------------------------------------------
    // DATE VALIDATION
    // --------------------------------------------------

    function clampDate(date, input) {
        const min = dateBound(input, 'min');
        const max = dateBound(input, 'max');

        let result = startOfDay(date);

        if (min && result < min) {
            result = min;
        }

        if (max && result > max) {
            result = max;
        }

        return result;
    }

    // --------------------------------------------------
    // OPEN PICKER
    // --------------------------------------------------

    function openPicker(input) {
        if (
            !input ||
            !input.isConnected ||
            input.disabled ||
            input.dataset.k8pickerOriginalReadonly === '1'
        ) {
            return;
        }

        if (activeInput === input && popup) {
            return;
        }

        closePicker();

        prepareInput(input);

        activeInput = input;
        currentType = getInputType(input);

        const parsedDate = parseDateValue(input.value);

        selectedDate = clampDate(
            parsedDate || new Date(),
            input
        );

        viewDate = new Date(
            selectedDate.getFullYear(),
            selectedDate.getMonth(),
            1
        );

        const parsedTime = parseTimeValue(input.value);

        selectedHour = parsedTime.hour;
        selectedMinute = parsedTime.minute;
        clampTime(input);

        popup = document.createElement('div');

        popup.className = 'k8p-popup';
        popup.setAttribute('role', 'dialog');

        popup.setAttribute(
            'aria-label',
            currentType === 'time'
                ? 'Select time'
                : 'Select date and time'
        );

        popup.addEventListener('click', event => {
            event.stopPropagation();
        });

        document.body.appendChild(popup);

        input.setAttribute('aria-expanded', 'true');

        render();
        positionPopup();
    }

    // --------------------------------------------------
    // POPUP POSITION
    // --------------------------------------------------

    function positionPopup() {
        if (!popup || !activeInput) {
            return;
        }

        const rect = activeInput.getBoundingClientRect();
        const popupRect = popup.getBoundingClientRect();

        const width = popupRect.width || 300;
        const height = popupRect.height;

        const margin = 10;

        let left = Math.max(
            margin,
            Math.min(
                rect.left,
                window.innerWidth - width - margin
            )
        );

        let top = rect.bottom + 5;

        if (
            top + height > window.innerHeight - margin &&
            rect.top > height + 5
        ) {
            top = rect.top - height - 5;
        }

        top = Math.max(
            margin,
            Math.min(
                top,
                window.innerHeight - height - margin
            )
        );

        popup.style.left = left + 'px';
        popup.style.top = top + 'px';
    }

    // --------------------------------------------------
    // CLOSE PICKER
    // --------------------------------------------------

    function closePicker() {
        if (activeInput) {
            activeInput.setAttribute('aria-expanded', 'false');
        }

        if (popup) {
            popup.remove();
        }

        popup = null;
        activeInput = null;
    }

    // --------------------------------------------------
    // RENDER
    // --------------------------------------------------

    function render() {
        if (!popup) return;

        popup.innerHTML = '';

        if (currentType !== 'time') {
            renderCalendar();
        }

        if (currentType !== 'date') {
            renderTime();
        }

        renderFooter();

        positionPopup();
    }

    // --------------------------------------------------
    // CALENDAR
    // --------------------------------------------------

    function renderCalendar() {
        const year = viewDate.getFullYear();
        const month = viewDate.getMonth();

        const header = document.createElement('div');

        header.className = 'k8p-header';

        const title = document.createElement('div');

        title.className = 'k8p-title';

        title.textContent = viewDate.toLocaleString(
            undefined,
            {
                month: 'long',
                year: 'numeric'
            }
        );

        const nav = document.createElement('div');

        nav.className = 'k8p-nav';

        const prev = document.createElement('button');

        prev.type = 'button';
        prev.className = 'k8p-btn';
        prev.textContent = '‹';

        prev.setAttribute('aria-label', 'Previous month');

        const minDateForNav = activeInput ? dateBound(activeInput, 'min') : null;
        const maxDateForNav = activeInput ? dateBound(activeInput, 'max') : null;
        const previousMonthEnd = new Date(year, month, 0);
        const nextMonthStart = new Date(year, month + 1, 1);
        prev.disabled = Boolean(minDateForNav && previousMonthEnd < minDateForNav);

        prev.addEventListener('click', function () {
            viewDate = new Date(
                year,
                month - 1,
                1
            );

            render();
        });

        const next = document.createElement('button');

        next.type = 'button';
        next.className = 'k8p-btn';
        next.textContent = '›';

        next.setAttribute('aria-label', 'Next month');
        next.disabled = Boolean(maxDateForNav && nextMonthStart > maxDateForNav);

        next.addEventListener('click', function () {
            viewDate = new Date(
                year,
                month + 1,
                1
            );

            render();
        });

        nav.append(prev, next);
        header.append(title, nav);

        popup.appendChild(header);

        const calendar = document.createElement('div');

        calendar.className = 'k8p-calendar';

        ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'].forEach(day => {
            const cell = document.createElement('div');

            cell.className = 'k8p-weekday';
            cell.textContent = day;

            calendar.appendChild(cell);
        });

        const firstDay = new Date(
            year,
            month,
            1
        ).getDay();

        const daysInMonth = new Date(
            year,
            month + 1,
            0
        ).getDate();

        const min = activeInput
            ? dateBound(activeInput, 'min')
            : null;

        const max = activeInput
            ? dateBound(activeInput, 'max')
            : null;

        const today = startOfDay(new Date());

        for (let i = 0; i < firstDay; i++) {
            const blank = document.createElement('div');

            blank.className = 'k8p-day empty';

            calendar.appendChild(blank);
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const button = document.createElement('button');

            const cellDate = new Date(
                year,
                month,
                day
            );

            button.type = 'button';
            button.className = 'k8p-day';
            button.textContent = String(day);

            if (cellDate.getTime() === selectedDate.getTime()) {
                button.classList.add('selected');
            }

            if (cellDate.getTime() === today.getTime()) {
                button.classList.add('today');
            }

            button.disabled = Boolean(
                (min && cellDate < min) ||
                (max && cellDate > max)
            );

            button.setAttribute(
                'aria-label',
                cellDate.toLocaleDateString()
            );

            button.setAttribute(
                'aria-pressed',
                String(cellDate.getTime() === selectedDate.getTime())
            );

            button.addEventListener('click', function () {
                selectedDate = cellDate;
                clampTime(activeInput);
                render();
            });

            calendar.appendChild(button);
        }

        popup.appendChild(calendar);
    }

    // --------------------------------------------------
    // TIME SELECT OPTIONS
    // --------------------------------------------------

    function createSelect(label, values, selectedValue, onChange, isDisabled) {
        const select = document.createElement('select');

        select.setAttribute('aria-label', label);

        values.forEach(item => {
            const option = document.createElement('option');

            option.value = String(item.value);
            option.textContent = item.label;
            option.disabled = Boolean(isDisabled && isDisabled(Number(item.value)));

            select.appendChild(option);
        });

        select.value = String(selectedValue);

        select.addEventListener('change', function () {
            onChange(Number(select.value));
        });

        return select;
    }

    // --------------------------------------------------
    // TIME PICKER — 12 HOUR WITH AM / PM
    // --------------------------------------------------

    function renderTime() {
        const wrapper = document.createElement('div');
        wrapper.className = 'k8p-time';

        const displayHour = (selectedHour % 12) || 12;
        const period = selectedHour >= 12 ? 1 : 0;

        const hours = Array.from({ length: 12 }, (_, index) => ({
            value: index + 1,
            label: pad(index + 1)
        }));

        const minutes = Array.from({ length: 60 }, (_, value) => ({
            value,
            label: pad(value)
        }));

        const hourSelect = createSelect(
            'Hour', hours, displayHour,
            value => {
                const isPM = selectedHour >= 12;
                selectedHour = (value % 12) + (isPM ? 12 : 0);
                clampTime(activeInput);
                render();
            },
            value => {
                const hour24 = (value % 12) + (period ? 12 : 0);
                return !Array.from({ length: 60 }, (_, minute) => minute)
                    .some(minute => !timeOptionDisabled(activeInput, hour24, minute));
            }
        );

        const minuteSelect = createSelect(
            'Minute', minutes, selectedMinute,
            value => {
                selectedMinute = value;
                clampTime(activeInput);
                render();
            },
            value => timeOptionDisabled(activeInput, selectedHour, value)
        );

        const periodSelect = createSelect(
            'AM or PM',
            [{ value: 0, label: 'AM' }, { value: 1, label: 'PM' }],
            period,
            value => {
                const hour12 = (selectedHour % 12) || 12;
                selectedHour = (hour12 % 12) + (value === 1 ? 12 : 0);
                clampTime(activeInput);
                render();
            },
            value => !periodHasAvailableTime(activeInput, value)
        );

        wrapper.append(
            hourSelect,
            document.createTextNode(':'),
            minuteSelect,
            periodSelect
        );

        popup.appendChild(wrapper);
    }

    // --------------------------------------------------
    // FOOTER
    // --------------------------------------------------

    function renderFooter() {
        const footer = document.createElement('div');

        footer.className = 'k8p-footer';

        const cancel = document.createElement('button');

        cancel.type = 'button';
        cancel.className = 'k8p-btn';
        cancel.textContent = 'Cancel';

        cancel.addEventListener('click', closePicker);

        footer.appendChild(cancel);

        if (currentType !== 'time') {
            const today = document.createElement('button');

            today.type = 'button';
            today.className = 'k8p-btn k8p-today';
            today.textContent = 'Today';

            const todayDate = startOfDay(new Date());

            const min = activeInput
                ? dateBound(activeInput, 'min')
                : null;

            const max = activeInput
                ? dateBound(activeInput, 'max')
                : null;

            today.disabled = Boolean(
                (min && todayDate < min) ||
                (max && todayDate > max)
            );

            today.addEventListener('click', function () {
                selectedDate = todayDate;
                clampTime(activeInput);

                viewDate = new Date(
                    todayDate.getFullYear(),
                    todayDate.getMonth(),
                    1
                );

                render();
            });

            footer.appendChild(today);
        }

        const done = document.createElement('button');

        done.type = 'button';
        done.className = 'k8p-btn k8p-done';
        done.textContent = 'Done';
        done.disabled = Boolean(
            (activeInput && dateBound(activeInput, 'min') && dateBound(activeInput, 'max') &&
             dateBound(activeInput, 'min') > dateBound(activeInput, 'max')) ||
            (activeInput && !hasValidTimeRange(activeInput))
        );

        done.addEventListener('click', saveValue);

        footer.appendChild(done);

        popup.appendChild(footer);
    }

    // --------------------------------------------------
    // SAVE VALUE
    // --------------------------------------------------

    function saveValue() {
        if (!activeInput || !activeInput.isConnected) {
            closePicker();
            return;
        }

        // Final validation protects values even if attributes changed while open.
        selectedDate = clampDate(selectedDate, activeInput);
        clampTime(activeInput);

        if (!hasValidTimeRange(activeInput)) {
            alert('The minimum time cannot be later than the maximum time.');
            return;
        }

        let value = '';

        const dateValue = [
            selectedDate.getFullYear(),
            pad(selectedDate.getMonth() + 1),
            pad(selectedDate.getDate())
        ].join('-');

        const timeValue =
            pad(selectedHour) + ':' +
            pad(selectedMinute);

        if (currentType === 'date') {
            value = dateValue;
        } else if (currentType === 'time') {
            value = timeValue;
        } else {
            value = dateValue + 'T' + timeValue;
        }

        const input = activeInput;

        input.value = value;

        // Notify form handlers and other listeners.
        input.dispatchEvent(
            new Event('input', {
                bubbles: true
            })
        );

        input.dispatchEvent(
            new Event('change', {
                bubbles: true
            })
        );

        closePicker();
    }

    // --------------------------------------------------
    // COLLECT INPUTS
    // --------------------------------------------------

    function collectInputs(target) {
        if (!target) return [];

        // CSS selector
        if (typeof target === 'string') {
            try {
                let found = Array.from(
                    document.querySelectorAll(target)
                );

                // Compatibility with older .rm_date / .rm_time usage.
                if (!found.length && target.trim() === '.rm_date') {
                    found = Array.from(
                        document.querySelectorAll(
                            'input[name="rm_date"], input.k8-date'
                        )
                    );
                } else if (
                    !found.length &&
                    target.trim() === '.rm_time'
                ) {
                    found = Array.from(
                        document.querySelectorAll(
                            'input[name="rm_time"], input.k8-time'
                        )
                    );
                }

                return found;

            } catch (error) {
                console.error(
                    'K8Picker: Invalid selector:',
                    target,
                    error
                );

                return [];
            }
        }

        // Single input
        if (target instanceof HTMLInputElement) {
            return [target];
        }

        // Container, document or fragment
        if (
            target instanceof Element ||
            target instanceof Document ||
            target instanceof DocumentFragment
        ) {
            return Array.from(
                target.querySelectorAll('input')
            );
        }

        // NodeList, HTMLCollection or array
        if (typeof target[Symbol.iterator] === 'function') {
            return Array.from(target);
        }

        return [];
    }

    // --------------------------------------------------
    // PUBLIC FUNCTION
    // --------------------------------------------------

    function k8picker(target) {
        injectCSS();

        const inputs = collectInputs(target);

        inputs.forEach(input => {
            prepareInput(input);
        });

        return k8picker;
    }

    k8picker.__k8picker = true;
    k8picker.close = closePicker;

    window.k8picker = k8picker;

    // --------------------------------------------------
    // AUTO INITIALIZE AND DYNAMIC MODALS
    // --------------------------------------------------

    function startObserver() {
        if (observerStarted || !document.body) return;

        observerStarted = true;

        injectCSS();

        document
            .querySelectorAll(AUTO_SELECTOR)
            .forEach(prepareInput);

        const observer = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (!(node instanceof Element)) return;

                    if (node.matches(AUTO_SELECTOR)) {
                        prepareInput(node);
                    }

                    node
                        .querySelectorAll(AUTO_SELECTOR)
                        .forEach(prepareInput);
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            startObserver,
            { once: true }
        );
    } else {
        startObserver();
    }

    // --------------------------------------------------
    // OUTSIDE CLICK
    // --------------------------------------------------

    document.addEventListener('click', function (event) {
        if (!popup) return;

        if (
            popup.contains(event.target) ||
            event.target === activeInput
        ) {
            return;
        }

        closePicker();
    });

    // --------------------------------------------------
    // ESCAPE
    // --------------------------------------------------

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && popup) {
            closePicker();
        }
    });

    // --------------------------------------------------
    // WINDOW RESIZE / SCROLL
    // --------------------------------------------------

    window.addEventListener('resize', positionPopup);

    window.addEventListener(
        'scroll',
        positionPopup,
        true
    );

})();