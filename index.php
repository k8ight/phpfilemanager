<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> 
<html class="no-js" lang="en-us"> <!--<![endif]-->
<!-- Version: 1.5--><!--ownerInfo:SOUNAKKAR-->
<head>

	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="viewport" content="width=device-width" />
	<title>FILE MANAGER</title>
<link rel="icon" type="image\ico"  href="favicon.ico">
 <style>
 :root {
  --lines: #000000;
  --backcolor: #ffffff;
  --border: 1px solid black;
}

html{box-sizing:border-box}*,*:before,*:after{box-sizing:inherit}
html{-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%}body{margin:0}
article,aside,details,figcaption,figure,footer,header,main,menu,nav,section{display:block}summary{display:list-item}
audio,canvas,progress,video{display:inline-block}progress{vertical-align:baseline}
audio:not([controls]){display:none;height:0}[hidden],template{display:none}
a{background-color:transparent;text-decoration:inherit}a:active,a:hover{outline-width:0}
abbr[title]{border-bottom:none;text-decoration:underline;text-decoration:underline dotted}
b,strong{font-weight:bolder}dfn{font-style:italic}mark{background:#ff0;color:#000}
small{font-size:80%}sub,sup{font-size:75%;line-height:0;position:relative;vertical-align:baseline}
sub{bottom:-0.25em}sup{top:-0.5em}figure{margin:1em 40px}img{border-style:none}
code,kbd,pre,samp{font-family:monospace,monospace;font-size:1em}hr{box-sizing:content-box;height:0;overflow:visible}
button,input,select,textarea,optgroup{font:inherit;margin:0}optgroup{font-weight:bold}
button,input{overflow:visible}button,select{text-transform:none}
button,[type=button],[type=reset],[type=submit]{-webkit-appearance:button}
button::-moz-focus-inner,[type=button]::-moz-focus-inner,[type=reset]::-moz-focus-inner,[type=submit]::-moz-focus-inner{border-style:none;padding:0}
button:-moz-focusring,[type=button]:-moz-focusring,[type=reset]:-moz-focusring,[type=submit]:-moz-focusring{outline:1px dotted ButtonText}
fieldset{border:1px solid #c0c0c0;margin:0 2px;padding:.35em .625em .75em}
legend{color:inherit;display:table;max-width:100%;padding:0;white-space:normal}textarea{overflow:auto}
[type=checkbox],[type=radio]{padding:0}
[type=number]::-webkit-inner-spin-button,[type=number]::-webkit-outer-spin-button{height:auto}
[type=search]{-webkit-appearance:textfield;outline-offset:-2px}
[type=search]::-webkit-search-decoration{-webkit-appearance:none}
::-webkit-file-upload-button{-webkit-appearance:button;font:inherit}
/* End extract */
html,body{font-family:Roboto,Helvetica Neue,sans-serif;font-size:15px;line-height:1.5}html{overflow-x:hidden}
h1{font-size:36px}h2{font-size:30px}h3{font-size:24px}h4{font-size:20px}h5{font-size:18px}h6{font-size:16px}
.k8-serif{font-family:serif}.k8-sans-serif{font-family:sans-serif}.k8-cursive{font-family:cursive}.k8-monospace{font-family:monospace}.k8-osan{font-family: 'open_sanslight';}
h1,h2,h3,h4,h5,h6{font-family:"Segoe UI",Arial,sans-serif;font-weight:400;margin:10px 0}.k8-wide{letter-spacing:4px}
hr{border:0;border-top:1px solid #eee;margin:20px 0}
.k8-bold{font-weight:900}.k8-strong{font-weight:600}
.k8-image{max-width:100%;height:auto}img{vertical-align:middle}a{color:inherit}
.k8-table,.k8-table-all{border-collapse:collapse;border-spacing:0;width:100%;display:table}.k8-table-all{border:1px solid #ccc}
.k8-bordered tr,.k8-table-all tr{border-bottom:1px solid #ddd}.k8-striped tbody tr:nth-child(even){background-color:#f1f1f1}
.k8-table-all tr:nth-child(odd){background-color:#fff}.k8-table-all tr:nth-child(even){background-color:#f1f1f1}
.k8-hoverable tbody tr:hover,.k8-ul.k8-hoverable li:hover{background-color:#ccc}.k8-centered tr th,.k8-centered tr td,.k8-centered{text-align:center}
.k8-table td,.k8-table th,.k8-table-all td,.k8-table-all th{padding:8px 8px;display:table-cell;text-align:left;vertical-align:top}
.k8-table th:first-child,.k8-table td:first-child,.k8-table-all th:first-child,.k8-table-all td:first-child{padding-left:16px}
.k8-btn,.k8-button,.k8-bton{border:none;display:inline-block;padding:8px 16px;vertical-align:middle;overflow:hidden;text-decoration:none;color:inherit;background-color:inherit;text-align:center;cursor:pointer;white-space:nowrap}
.k8-bton{margin:2px 2px 2px 2px}.k8-bton:hover{box-shadow:0 8px 16px 0 rgba(0,0,0,0.2),0 6px 20px 0 rgba(0,0,0,0.19)}
.k8-btn,.k8-bton:hover,.k8-button{-webkit-touch-callout:none;-webkit-user-select:none;-khtml-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none}   
.k8-disabled,.k8-btn:disabled,.k8-button:disabled{cursor:not-allowed;opacity:0.3}.k8-disabled *,:disabled *{pointer-events:none}
.k8-btn.k8-disabled:hover,.k8-btn:disabled:hover{box-shadow:none}
.k8-badge,.k8-tag{background-color:#000;color:#fff;display:inline-block;padding-left:8px;padding-right:8px;text-align:center}.k8-badge{border-radius:50%}
.k8-ul{list-style-type:none;padding:0;margin:0}.k8-ul li{padding:8px 16px;border-bottom:1px solid #ddd}.k8-ul li:last-child{border-bottom:none}
.k8-tooltip,.k8-display-container{position:relative}.k8-tooltip .k8-text{display:none}.k8-tooltip:hover .k8-text{display:inline-block}
.k8-ripple:active{opacity:0.5}.k8-ripple{transition:opacity 0s}
.k8-input{padding:8px;display:block;border:none;border-bottom:1px solid #ccc;width:100%}.k8-filter{display:inline-block;width:300px;padding-left:8px;padding-right:8px}
.k8-select{padding:9px 0;width:100%;border:none;border-bottom:1px solid #ccc}.k8-textarea{padding:8px;display:block;border:none;border-bottom:1px solid #ccc;width:100%;resize: none}
.k8-dropdown-click,.k8-dropdown-hover{position:relative;display:inline-block;cursor:pointer}
.k8-dropdown-hover:hover .k8-dropdown-content{display:block}
.k8-dropdown-hover:first-child,.k8-dropdown-click:hover{background-color:#ccc;color:#000}
.k8-dropdown-hover:hover > .k8-button:first-child,.k8-dropdown-click:hover > .k8-button:first-child{opacity: 0.5}
.k8-dropdown-content{cursor:auto;color:#000;background-color:#fff;display:none;position:absolute;min-width:160px;margin:0;padding:0;z-index:1}
.k8-check,.k8-radio{width:24px;height:24px;position:relative;top:6px}
.k8-sidebar,.k8-sidebar-left{height:100%;width:200px;background-color:#fff;position:fixed!important;z-index:1;overflow:auto;box-shadow: 10px 0 5px -2px #888;}
.k8-sidebar-right{height:100%;width:200px;background-color:#fff;position:fixed!important;right:0;z-index:1;overflow:auto;box-shadow: -2px 0 5px 0px #888;}
.k8-bar-block .k8-dropdown-hover,.k8-bar-block .k8-dropdown-click{width:100%}
.k8-bar-block .k8-dropdown-hover .k8-dropdown-content,.k8-bar-block .k8-dropdown-click .k8-dropdown-content{min-width:100%}
.k8-bar-block .k8-dropdown-hover .k8-button,.k8-bar-block .k8-dropdown-click .k8-button{width:100%;text-align:left;padding:8px 16px}
.k8-main,#main{transition:margin-left .4s}
.k8-modal{z-index:3;display:none;padding-top:100px;position:fixed;left:0;top:0;width:100%;height:100%;overflow:auto;background-color:rgb(0,0,0);background-color:rgba(0,0,0,0.4)}
.k8-modal-content{margin:auto;background-color:#fff;position:relative;padding:0;outline:0;width:600px}
.k8-bar{width:100%;overflow:hidden}.k8-center .k8-bar{display:inline-block;width:auto}
.k8-bar .k8-bar-item{padding:8px 16px;float:left;width:auto;border:none;display:block;outline:0}
.k8-bar .k8-dropdown-hover,.k8-bar .k8-dropdown-click{position:static;float:left}
.k8-bar .k8-button{white-space:normal}
.k8-bar-block .k8-bar-item{width:100%;display:block;padding:8px 16px;text-align:left;border:none;white-space:normal;float:none;outline:0}
.k8-bar-block.k8-center .k8-bar-item{text-align:center}.k8-block{display:block;width:100%}
.k8-responsive{display:block;overflow-x:auto}
.k8-container:after,.k8-container:before,.k8-panel:after,.k8-panel:before,.k8-row:after,.k8-row:before,.k8-row-padding:after,.k8-row-padding:before,
.k8-cell-row:before,.k8-cell-row:after,.k8-clear:after,.k8-clear:before,.k8-bar:before,.k8-bar:after{content:"";display:table;clear:both}
.k8-col,.k8-half,.k8-third,.k8-twothird,.k8-threequarter,.k8-quarter{float:left;width:100%}.k8-columns{ display:inline-block;}
.k8-col.s1{width:8.33333%}.k8-col.s2{width:16.66666%}.k8-col.s3{width:24.99999%}.k8-col.s4{width:33.33333%}
.k8-col.s5{width:41.66666%}.k8-col.s6{width:49.99999%}.k8-col.s7{width:58.33333%}.k8-col.s8{width:66.66666%}
.k8-col.s9{width:74.99999%}.k8-col.s10{width:83.33333%}.k8-col.s11{width:91.66666%}.k8-col.s12{width:99.99999%}
@media (min-width:601px){.k8-col.m1{width:8.33333%}.k8-col.m2{width:16.66666%}.k8-col.m3,.k8-quarter{width:24.99999%}.k8-col.m4,.k8-third{width:33.33333%}
.k8-col.m5{width:41.66666%}.k8-col.m6,.k8-half{width:49.99999%}.k8-col.m7{width:58.33333%}.k8-col.m8,.k8-twothird{width:66.66666%}
.k8-col.m9,.k8-threequarter{width:74.99999%}.k8-col.m10{width:83.33333%}.k8-col.m11{width:91.66666%}.k8-col.m12{width:99.99999%}}
@media (min-width:993px){.k8-col.l1{width:8.33333%}.k8-col.l2{width:16.66666%}.k8-col.l3{width:24.99999%}.k8-col.l4{width:33.33333%}
.k8-col.l5{width:41.66666%}.k8-col.l6{width:49.99999%}.k8-col.l7{width:58.33333%}.k8-col.l8{width:66.66666%}
.k8-col.l9{width:74.99999%}.k8-col.l10{width:83.33333%}.k8-col.l11{width:91.66666%}.k8-col.l12{width:99.99999%}}
.k8-rest{overflow:hidden}.k8-stretch{margin-left:-16px;margin-right:-16px}
.k8-content,.k8-auto{margin-left:auto;margin-right:auto}.k8-content{max-width:980px}.k8-auto{max-width:1140px}.k8-mtop{margin-top:10px}
.k8-cell-row{display:table;width:100%}.k8-cell{display:table-cell}
.k8-cell-top{vertical-align:top}.k8-cell-middle{vertical-align:middle}.k8-cell-bottom{vertical-align:bottom}
.k8-hide{display:none!important}.k8-show-block,.k8-show{display:block!important}.k8-show-inline-block{display:inline-block!important}
@media (max-width:1205px){.k8-auto{max-width:95%}}
@media (max-width:600px){.k8-modal-content{margin:0 10px;width:auto!important}.k8-modal{padding-top:30px}
.k8-dropdown-hover.k8-mobile .k8-dropdown-content,.k8-dropdown-click.k8-mobile .k8-dropdown-content{position:relative}	
.k8-hide-small{display:none!important}.k8-mobile{display:block;width:100%!important}.k8-bar-item.k8-mobile,.k8-dropdown-hover.k8-mobile,.k8-dropdown-click.k8-mobile{text-align:center}
.k8-dropdown-hover.k8-mobile,.k8-dropdown-hover.k8-mobile .k8-btn,.k8-dropdown-hover.k8-mobile .k8-button,.k8-dropdown-click.k8-mobile,.k8-dropdown-click.k8-mobile .k8-btn,.k8-dropdown-click.k8-mobile .k8-button{width:100%}}
@media (max-width:768px){.k8-modal-content{width:500px}.k8-modal{padding-top:50px}}
@media (min-width:993px){.k8-modal-content{width:900px}.k8-hide-large{display:none!important}.k8-sidebar.k8-collapse{display:block!important}}
@media (max-width:992px) and (min-width:601px){.k8-hide-medium{display:none!important}}
@media (max-width:992px){.k8-sidebar.k8-collapse{display:none}.k8-main{margin-left:0!important;margin-right:0!important}.k8-auto{max-width:100%}}
.k8-top,.k8-bottom{position:fixed;width:100%;z-index:1}.k8-top{top:0}.k8-bottom{bottom:0}
.k8-overlay{position:fixed;display:none;width:100%;height:100%;top:0;left:0;right:0;bottom:0;background-color:rgba(0,0,0,0.5);z-index:2}
.k8-display-topleft{position:absolute;left:0;top:0}.k8-display-topright{position:absolute;right:0;top:0}
.k8-display-bottomleft{position:absolute;left:0;bottom:0}.k8-display-bottomright{position:absolute;right:0;bottom:0}
.k8-display-middle{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);-ms-transform:translate(-50%,-50%)}
.k8-display-left{position:absolute;top:50%;left:0%;transform:translate(0%,-50%);-ms-transform:translate(-0%,-50%)}
.k8-display-right{position:absolute;top:50%;right:0%;transform:translate(0%,-50%);-ms-transform:translate(0%,-50%)}
.k8-display-topmiddle{position:absolute;left:50%;top:0;transform:translate(-50%,0%);-ms-transform:translate(-50%,0%)}
.k8-display-bottommiddle{position:absolute;left:50%;bottom:0;transform:translate(-50%,0%);-ms-transform:translate(-50%,0%)}
.k8-display-container:hover .k8-display-hover{display:block}.k8-display-container:hover span.k8-display-hover{display:inline-block}.k8-display-hover{display:none}
.k8-display-position{position:absolute}
.k8-circle{border-radius:50%}
.k8-round-small{border-radius:2px}.k8-round,.k8-round-medium{border-radius:4px}.k8-round-large{border-radius:8px}.k8-round-xlarge{border-radius:16px}.k8-round-xxlarge{border-radius:32px}
.k8-row-padding,.k8-row-padding>.k8-half,.k8-row-padding>.k8-third,.k8-row-padding>.k8-twothird,.k8-row-padding>.k8-threequarter,.k8-row-padding>.k8-quarter,.k8-row-padding>.k8-col{padding:0 8px}
.k8-container,.k8-panel{padding:0.01em 16px}.k8-panel{margin-top:16px;margin-bottom:16px}
.k8-code,.k8-codespan{font-family:Consolas,"courier new";font-size:16px}
.k8-code{width:auto;background-color:#fff;padding:8px 12px;border-left:4px solid #4CAF50;word-wrap:break-word}
.k8-codespan{color:crimson;background-color:#f1f1f1;padding-left:4px;padding-right:4px;font-size:110%}
.k8-card,.k8-card-2{box-shadow:0 2px 5px 0 rgba(0,0,0,0.16),0 2px 10px 0 rgba(0,0,0,0.12)}
.k8-card-4,.k8-hover-shadow:hover{box-shadow:0 4px 10px 0 rgba(0,0,0,0.2),0 4px 20px 0 rgba(0,0,0,0.19)}
.k8-spin{animation:k8-spin 2s infinite linear}@keyframes k8-spin{0%{transform:rotate(0deg)}100%{transform:rotate(359deg)}}
.k8-animate-fading{animation:fading 10s infinite}@keyframes fading{0%{opacity:0}50%{opacity:1}100%{opacity:0}}
.k8-animate-opacity{animation:opac 0.8s}@keyframes opac{from{opacity:0} to{opacity:1}}
.k8-animate-top{position:relative;animation:animatetop 0.4s}@keyframes animatetop{from{top:-300px;opacity:0} to{top:0;opacity:1}}
.k8-animate-left{position:relative;animation:animateleft 0.4s}@keyframes animateleft{from{left:-300px;opacity:0} to{left:0;opacity:1}}
.k8-animate-right{position:relative;animation:animateright 0.4s}@keyframes animateright{from{right:-300px;opacity:0} to{right:0;opacity:1}}
.k8-animate-bottom{position:relative;animation:animatebottom 0.4s}@keyframes animatebottom{from{bottom:-300px;opacity:0} to{bottom:0;opacity:1}}
.k8-animate-zoom {animation:animatezoom 0.6s}@keyframes animatezoom{from{transform:scale(0)} to{transform:scale(1)}}
.k8-animate-input{transition:width 0.4s ease-in-out}.k8-animate-input:focus{width:100%!important}
.k8-opacity,.k8-hover-opacity:hover{opacity:0.60}.k8-opacity-off,.k8-hover-opacity-off:hover{opacity:1}
.k8-opacity-max{opacity:0.25}.k8-opacity-min{opacity:0.75}
.k8-greyscale-max,.k8-grayscale-max,.k8-hover-greyscale:hover,.k8-hover-grayscale:hover{filter:grayscale(100%)}
.k8-greyscale,.k8-grayscale{filter:grayscale(75%)}.k8-greyscale-min,.k8-grayscale-min{filter:grayscale(50%)}
.k8-sepia{filter:sepia(75%)}.k8-sepia-max,.k8-hover-sepia:hover{filter:sepia(100%)}.k8-sepia-min{filter:sepia(50%)}
.k8-tiny{font-size:10px!important}.k8-small{font-size:12px!important}.k8-medium{font-size:15px!important}.k8-large{font-size:18px!important}
.k8-xlarge{font-size:24px!important}.k8-xxlarge{font-size:36px!important}.k8-xxxlarge{font-size:48px!important}.k8-jumbo{font-size:64px!important}
.k8-left-align{text-align:left!important}.k8-right-align{text-align:right!important}.k8-justify{text-align:justify!important}.k8-center{text-align:center!important}
.k8-border-0{border:0!important}.k8-border{border:1px solid #ccc!important}
.k8-border-top{border-top:1px solid #ccc!important}.k8-border-bottom{border-bottom:1px solid #ccc!important}
.k8-border-left{border-left:1px solid #ccc!important}.k8-border-right{border-right:1px solid #ccc!important}
.k8-border-bar{border:6px solid #ccc!important}.k8-topbar{border-top:6px solid #ccc!important}.k8-bottombar{border-bottom:6px solid #ccc!important}
.k8-leftbar{border-left:6px solid #ccc!important}.k8-rightbar{border-right:6px solid #ccc!important}
.k8-section,.k8-code{margin-top:16px!important;margin-bottom:16px!important}
.k8-margin{margin:16px!important}.k8-margin-top{margin-top:16px!important}.k8-margin-bottom{margin-bottom:16px!important}
.k8-margin-left{margin-left:16px!important}.k8-margin-right{margin-right:16px!important}
.k8-padding-small{padding:4px 8px!important}.k8-padding{padding:8px 16px!important}.k8-padding-large{padding:12px 24px!important}
.k8-padding-16{padding-top:16px!important;padding-bottom:16px!important}.k8-padding-24{padding-top:24px!important;padding-bottom:24px!important}
.k8-padding-32{padding-top:32px!important;padding-bottom:32px!important}.k8-padding-48{padding-top:48px!important;padding-bottom:48px!important}
.k8-padding-64{padding-top:64px!important;padding-bottom:64px!important}
.k8-padding-top-64{padding-top:64px!important}.k8-padding-top-48{padding-top:48px!important}
.k8-padding-top-32{padding-top:32px!important}.k8-padding-top-24{padding-top:24px!important}
.k8-left{float:left!important}.k8-right{float:right!important}
.k8-button:hover{opacity: 0.7!important}
.k8-transparent,.k8-hover-none:hover{background-color:transparent!important}
.k8-hover-none:hover{box-shadow:none!important}
/* Colors */
.k8-amber,.k8-hover-amber:hover{color:#000!important;background-color:#ffc107!important}
.k8-aqua,.k8-hover-aqua:hover{color:#000!important;background-color:#00ffff!important}
.k8-blue,.k8-hover-blue:hover{color:#fff!important;background-color:#2196F3!important}
.k8-light-blue,.k8-hover-light-blue:hover{color:#000!important;background-color:#87CEEB!important}
.k8-brown,.k8-hover-brown:hover{color:#fff!important;background-color:#795548!important}
.k8-cyan,.k8-hover-cyan:hover{color:#000!important;background-color:#00bcd4!important}
.k8-blue-grey,.k8-hover-blue-grey:hover,.k8-blue-gray,.k8-hover-blue-gray:hover{color:#fff!important;background-color:#607d8b!important}
.k8-green,.k8-hover-green:hover{color:#fff!important;background-color:#4CAF50!important}
.k8-light-green,.k8-hover-light-green:hover{color:#000!important;background-color:#8bc34a!important}
.k8-indigo,.k8-hover-indigo:hover{color:#fff!important;background-color:#3f51b5!important}
.k8-khaki,.k8-hover-khaki:hover{color:#000!important;background-color:#f0e68c!important}
.k8-lime,.k8-hover-lime:hover{color:#000!important;background-color:#cddc39!important}
.k8-orange,.k8-hover-orange:hover{color:#000!important;background-color:#ff9800!important}
.k8-deep-orange,.k8-hover-deep-orange:hover{color:#fff!important;background-color:#ff5722!important}
.k8-pink,.k8-hover-pink:hover{color:#fff!important;background-color:#e91e63!important}
.k8-purple,.k8-hover-purple:hover{color:#fff!important;background-color:#9c27b0!important}
.k8-deep-purple,.k8-hover-deep-purple:hover{color:#fff!important;background-color:#673ab7!important}
.k8-red,.k8-hover-red:hover{color:#fff!important;background-color:#f44336!important}
.k8-sand,.k8-hover-sand:hover{color:#000!important;background-color:#fdf5e6!important}
.k8-teal,.k8-hover-teal:hover{color:#fff!important;background-color:#009688!important}
.k8-yellow,.k8-hover-yellow:hover{color:#000!important;background-color:#ffeb3b!important}
.k8-white,.k8-hover-white:hover{color:#000!important;background-color:#fff!important}
.k8-black,.k8-hover-black:hover{color:#fff!important;background-color:#000!important}
.k8-theme,.k8-hover-theme:hover{color:#fff!important;background-color:#f4511e!important}
.k8-yellow-green,.k8-hover-yellow-green:hover{color:#000!important;background-color: #fafffa!important}
.k8-grey,.k8-hover-grey:hover,.k8-gray,.k8-hover-gray:hover{color:#000!important;background-color:#9e9e9e!important}
.k8-light-grey,.k8-hover-light-grey:hover,.k8-light-gray,.k8-hover-light-gray:hover{color:#000!important;background-color:#f1f1f1!important}
.k8-dark-grey,.k8-hover-dark-grey:hover,.k8-dark-gray,.k8-hover-dark-gray:hover{color:#fff!important;background-color:#616161!important}
.k8-pale-red,.k8-hover-pale-red:hover{color:#000!important;background-color:#ffdddd!important}
.k8-pale-green,.k8-hover-pale-green:hover{color:#000!important;background-color:#ddffdd!important}
.k8-pale-yellow,.k8-hover-pale-yellow:hover{color:#000!important;background-color:#ffffcc!important}
.k8-pale-blue,.k8-hover-pale-blue:hover{color:#000!important;background-color:#ddffff!important}
.k8-text-amber,.k8-hover-text-amber:hover{color:#ffc107!important}
.k8-text-aqua,.k8-hover-text-aqua:hover{color:#00ffff!important}
.k8-text-blue,.k8-hover-text-blue:hover{color:#2196F3!important}
.k8-text-light-blue,.k8-hover-text-light-blue:hover{color:#87CEEB!important}
.k8-text-brown,.k8-hover-text-brown:hover{color:#795548!important}
.k8-text-cyan,.k8-hover-text-cyan:hover{color:#00bcd4!important}
.k8-text-blue-grey,.k8-hover-text-blue-grey:hover,.k8-text-blue-gray,.k8-hover-text-blue-gray:hover{color:#607d8b!important}
.k8-text-green,.k8-hover-text-green:hover{color:#4CAF50!important}
.k8-text-light-green,.k8-hover-text-light-green:hover{color:#8bc34a!important}
.k8-text-indigo,.k8-hover-text-indigo:hover{color:#3f51b5!important}
.k8-text-khaki,.k8-hover-text-khaki:hover{color:#b4aa50!important}
.k8-text-lime,.k8-hover-text-lime:hover{color:#cddc39!important}
.k8-text-orange,.k8-hover-text-orange:hover{color:#ff9800!important}
.k8-text-deep-orange,.k8-hover-text-deep-orange:hover{color:#ff5722!important}
.k8-text-pink,.k8-hover-text-pink:hover{color:#e91e63!important}
.k8-text-purple,.k8-hover-text-purple:hover{color:#9c27b0!important}
.k8-text-deep-purple,.k8-hover-text-deep-purple:hover{color:#673ab7!important}
.k8-text-red,.k8-hover-text-red:hover{color:#f44336!important}
.k8-text-sand,.k8-hover-text-sand:hover{color:#fdf5e6!important}
.k8-text-teal,.k8-hover-text-teal:hover{color:#009688!important}
.k8-text-yellow,.k8-hover-text-yellow:hover{color:#FFFF00!important}
.k8-text-theme,.k8-hover-text-theme:hover{color:#F4511E!important}
.k8-text-white,.k8-hover-text-white:hover{color:#fff!important}
.k8-text-black,.k8-hover-text-black:hover{color:#000!important}
.k8-text-grey,.k8-hover-text-grey:hover,.k8-text-gray,.k8-hover-text-gray:hover{color:#757575!important}
.k8-text-light-grey,.k8-hover-text-light-grey:hover,.k8-text-light-gray,.k8-hover-text-light-gray:hover{color:#f1f1f1!important}
.k8-text-dark-grey,.k8-hover-text-dark-grey:hover,.k8-text-dark-gray,.k8-hover-text-dark-gray:hover{color:#3a3a3a!important}
.k8-border-amber,.k8-hover-border-amber:hover{border-color:#ffc107!important}
.k8-border-aqua,.k8-hover-border-aqua:hover{border-color:#00ffff!important}
.k8-border-blue,.k8-hover-border-blue:hover{border-color:#2196F3!important}
.k8-border-light-blue,.k8-hover-border-light-blue:hover{border-color:#87CEEB!important}
.k8-border-brown,.k8-hover-border-brown:hover{border-color:#795548!important}
.k8-border-cyan,.k8-hover-border-cyan:hover{border-color:#00bcd4!important}
.k8-border-blue-grey,.k8-hover-border-blue-grey:hover,.k8-border-blue-gray,.k8-hover-border-blue-gray:hover{border-color:#607d8b!important}
.k8-border-green,.k8-hover-border-green:hover{border-color:#4CAF50!important}
.k8-border-light-green,.k8-hover-border-light-green:hover{border-color:#8bc34a!important}
.k8-border-indigo,.k8-hover-border-indigo:hover{border-color:#3f51b5!important}
.k8-border-khaki,.k8-hover-border-khaki:hover{border-color:#f0e68c!important}
.k8-border-lime,.k8-hover-border-lime:hover{border-color:#cddc39!important}
.k8-border-orange,.k8-hover-border-orange:hover{border-color:#ff9800!important}
.k8-border-deep-orange,.k8-hover-border-deep-orange:hover{border-color:#ff5722!important}
.k8-border-pink,.k8-hover-border-pink:hover{border-color:#e91e63!important}
.k8-border-purple,.k8-hover-border-purple:hover{border-color:#9c27b0!important}
.k8-border-deep-purple,.k8-hover-border-deep-purple:hover{border-color:#673ab7!important}
.k8-border-red,.k8-hover-border-red:hover{border-color:#f44336!important}
.k8-border-sand,.k8-hover-border-sand:hover{border-color:#fdf5e6!important}
.k8-border-teal,.k8-hover-border-teal:hover{border-color:#009688!important}
.k8-border-theme,.k8-hover-border-theme:hover{border-color:#f4511e!important}
.k8-border-yellow,.k8-hover-border-yellow:hover{border-color:#ffeb3b!important}
.k8-border-white,.k8-hover-border-white:hover{border-color:#fff!important}
.k8-border-black,.k8-hover-border-black:hover{border-color:#000!important}
.k8-border-grey,.k8-hover-border-grey:hover,.k8-border-gray,.k8-hover-border-gray:hover{border-color:#9e9e9e!important}
.k8-border-light-grey,.k8-hover-border-light-grey:hover,.k8-border-light-gray,.k8-hover-border-light-gray:hover{border-color:#f1f1f1!important}
.k8-border-dark-grey,.k8-hover-border-dark-grey:hover,.k8-border-dark-gray,.k8-hover-border-dark-gray:hover{border-color:#616161!important}
.k8-border-pale-red,.k8-hover-border-pale-red:hover{border-color:#ffe7e7!important}.k8-border-pale-green,.k8-hover-border-pale-green:hover{border-color:#e7ffe7!important}
.k8-border-pale-yellow,.k8-hover-border-pale-yellow:hover{border-color:#ffffcc!important}.k8-border-pale-blue,.k8-hover-border-pale-blue:hover{border-color:#e7ffff!important}
.k8-admin-table tr td:nth-child(1){width:256px !important;}
.k8-alert { padding: 20px; z-index:100;position:fixed}.k8-closebtn {margin-left: 15px; color: white; font-weight: bold; float: right;font-size: 22px;line-height: 20px; cursor: pointer;  transition: 0.3s}.k8-closebtn:hover {  color: black}
.k8-altmtr{width:100%; -webkit-appearance: none; height: 4px;}
.k8-altmtr::-webkit-meter-optimum-value {
  background: #f4511e;
}
.k8-altmtr::-moz-meter-bar { /* Firefox Pseudo Class */
  background: #f4511e;
}
.k8-loader {
 border: 8px solid #f3f3f3;
  border-radius: 50%;
  border-top: 8px solid blue;
  border-bottom: 8px solid blue;
  width:64px;
  height: 64px;
  -webkit-animation: spin 2s linear infinite;
  animation: spin 2s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
.k8-pricelist{
	list-style-type: none;
  border: 1px solid #eee;
  margin: 5px;
  padding: 0;
  -webkit-transition: 0.3s;
  transition: 0.3s;
}
.k8-pricelist .header {
  background-color: #111;
  color: white;
  font-size: 25px;
}
.k8-pricelist li {
  border-bottom: 1px solid #eee;
  padding: 15px;
  text-align: center;
  background-color: #fafffa;
}
.k8-short{}
.k8-box{
	display:none;
z-index:50;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
min-height:auto;
min-width:512px;
box-sizing: border-box;
box-shadow: -2px 8px 15px #8a795d,
	2px 8px 15px #8a795d
}.k8-invoice{
  border: 2px solid black;
  border-collapse: collapse
}.k8-invoice td,.k8-invoice th{
	  border: 1px solid black;
  border-collapse: collapse
}
.k8-table-border{
	border:1px solid;
}
.k8-scroll-y{
	overflow-y:scroll;
}
.k8-scroll-x{
	overflow-x:scroll;
}
.k8-box .whead{
	
	box-sizing: border-box;
	width:100%;
	background-color:#2196F3;
	display:inline-block;
	padding:4px 6px;
	cursor:pointer;
	
}
.k8-box .whead span{
	color:#ffffff;
	font-size:18px;
	font-weight:600;
	vertical-align:baseline;
}
.k8-box .whead button{
 height:32px;
 width:32px;
 background-color:#f4511e;
 color:#FFFFFF;
 font-weight:900;
 font-size:18px;
 float:right;
 cursor:pointer;
}
/*BRACKETS*/
$side-margin: 50px;
$vertical-margin: 10px;

.k8-bracket{
  display: flex;
  height: 600px;
  justify-content: center;
}

.k8-brac-item{
  display: flex;
  flex-direction: row-reverse;
  p{
    margin: 0;
    background-color:var(--backcolor);
	border: var(--border);
  }
  
  &-parent{
    position: relative;
    margin-left: $side-margin;
    display: flex;
    align-items: center;
    &:after{
        position: absolute;
        content: '';
        width: $side-margin/2;
        height: 2px;
        left: 0;
        top: 50%;
        background-color: var(--lines);
        transform: translateX(-100%);
    }
  }
  &-childrens{
    display: flex;
    flex-direction: column;
    justify-content: center;
  }
  &-child{
    display: flex;
    align-items: flex-start;
    justify-content: flex-end;
    margin-top: $vertical-margin;
    margin-bottom: $vertical-margin;
    position: relative;
    &:before{
      content: '';
      position: absolute;
      background-color: var(--lines);
      right: 0;
      top: 50%;
      transform: translateX(100%);
      width: 25px;
      height: 2px;
    }
    &:after{
      content: '';
      position: absolute;
      background-color: var(--lines);
      right: -$side-margin / 2;
      height: calc(50% + 22px) ;
      width: 2px;
      top: 50%;
    }
    &:last-child{
      &:after{
        transform: translateY(-100%);
      }
    }
    &:only-child:after{          
      display: none;          
    }
  }
}


.k8-box .wbody{
	border:1px solid #2196F3;
	box-sizing: border-box;
	width:100%;
	height:auto;
	max-height:600px;
	 background-color:#ffffff;
	 overflow:auto;
}
@media (max-width:500px){
	body{width:100%;}
	.k8-table{
		overflow:scroll;
	}
	.k8-admin-table tr td:nth-child(1){width:inherit !important;}
	.k8-box{
		min-width:100vw;
		max-width:100vw;		
	top: 0px !important;
    left: 0%;
	transform: translate(0%, 0%);
	}
	.k8-box .whead button{
 height:32px;
 width:32px;
 background-color:#f4511e;
 color:#FFFFFF;
 font-weight:900;
 font-size:18px;
 float:right;
}
.k8-box .wbody{
max-height:80vh;	
}
.k8-button,.k8-btn,.k8-section-8{
	margin-top:8px;
}
.k8-short a{font-size: 0;}
.k8-short a:after{font-size: 18px;content: "KOC v-0.5";display:inline-block;}
.k8-sidebar{
	margin-top:32px;
}
}
.fx{
	text-align:center;
	font-size:64px;
}
@font-face {
  font-display: fallback; /* Define how the browser behaves during download */
}

.mh{
	max-width:32px;
}
table  tr  td:nth-child(1) {
	font-size:32px;
	max-width:32px;
}
.k8-table{
min-width:512px;
}
 </style>
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<?php
function pfs_fol ($dir)
{
    $siz = 0;

    foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
        $siz += is_file($each) ? filesize($each) : pfs_fol($each);
    }
	$size=pfs($siz);
	
	return $size;
}
function pfs_fol_bits ($dir)
{
    $siz = 0;

    foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
        $siz += is_file($each) ? filesize($each) : pfs_fol_bits($each);
    }
	$size=pfs_bits($siz);
	
	return $size;
}
function icon($ext){
	
	$icons=array(
	"html"=>"fa-brands fa-html5",
	"htm"=>"fa-brands fa-html5",
	"php"=>"fa-brands fa-php",
	"css"=>"fa-brands fa-css3",
	"scss"=>"fa-brands fa-css3",
	"js"=>"fa-brands fa-js",
	"json"=>"fa-solid fa-file-code",
	"xml"=>"fa-solid fa-file-code",
	"sql"=>"fa-solid fa-database",
	"sh"=>"fa-solid fa-terminal",
	"cmd"=>"fa-solid fa-terminal",
	"ps1"=>"fa-solid fa-terminal",
	"conf"=>"fa-solid fa-gears",
	"config"=>"fa-solid fa-gears",
	"cfg"=>"fa-solid fa-gears",
	"ini"=>"fa-solid fa-gears",
	"rar"=>"fa-solid fa-file-zipper",
	"zip"=>"fa-solid fa-file-zipper",
	"arc"=>"fa-solid fa-file-zipper",
	"7z"=>"fa-solid fa-file-zipper",
	"pdf"=>"fa-solid fa-pdf",
	"doc"=>"fa-solid fa-file-words",
	"docx"=>"fa-solid fa-file-words",
	"csv"=>"fa-solid fa-file-csv",
	"jpg"=>"fa-solid fa-file-image",
	"png"=>"fa-solid fa-file-image",
	"bmp"=>"fa-solid fa-file-image",
	"ico"=>"fa-solid fa-file-image",
	"jpeg"=>"fa-solid fa-file-image",
	"psd"=>"fa-solid fa-file-image",
	"xcf"=>"fa-solid fa-file-image",
	"mp4"=>"fa-solid fa-file-video",
	"webm"=>"fa-solid fa-file-video",
	"mkv"=>"fa-solid fa-file-video",
	"mpeg"=>"fa-solid fa-file-video",
	"raw"=>"fa-solid fa-file-video",
	"img"=>"fa-solid fa-compact-disc",
	"iso"=>"fa-solid fa-compact-disc",
	"daa"=>"fa-solid fa-compact-disc",
	"bin"=>"fa-solid fa-compact-disc",
	"mp3"=>"fa-solid fa-file-audio",
	"wav"=>"fa-solid fa-file-audio",
	"ogg"=>"fa-solid fa-file-audio",
	"ac3"=>"fa-solid fa-file-audio",
    );
	if(array_key_exists($ext,$icons)){
	return $icons[$ext];
	}else{
		return "fa-solid fa-file-circle-question";
	}
}

function pfs($fileSizeInBytes) {
  $i = -1;
  $byteUnits = array(' KiB', ' MiB', ' GiB', ' TiB', 'PiB', 'EiB', 'ZiB', 'YiB');
 do {
    $fileSizeInBytes = $fileSizeInBytes / 1024;
    $i++;
  } while ($fileSizeInBytes > 1024);
 return  round($fileSizeInBytes,2).$byteUnits[$i];
}

function pfs_bits($fileSizeInBytes) {
  $i = -1;
  $byteUnits = array(' KB', ' MB', ' GB', ' TB', 'PB', 'EB', 'ZB', 'YB');
 do {
    $fileSizeInBytes = $fileSizeInBytes / 1000;
    $i++;
  } while ($fileSizeInBytes > 1000);
 return  round($fileSizeInBytes,2).$byteUnits[$i];
}
function is_site($dir){
	if(file_exists($dir."/index.php")){
		return true;
	}else{
		return false;
	}
}
if(isset($_REQUEST['dir'])){
$directory =(empty($_REQUEST['dir'])? "./":$_REQUEST['dir']."/");
}else{
$directory ="./";
}
$cdir=rtrim($directory,"/");
$blocked=array("index");
?>
<body style="overflow-x:scroll">
 <div class="k8-padding" >
 <table class="k8-table k8-bordered k8-border k8-white">
<tr style="font-size:18px;">
<th colspan="5" class="k8-indigo"><i class="fa-regular fa-folder-open"></i> K8FM : <?php echo ((substr($directory, 0, strrpos($directory, '/'))==".")? "< root >":substr($directory, 0, strrpos($directory, '/')));?></th>
<th colspan="1" class="k8-indigo"><div style="min-width:220px;">
<a href="./?dir=<?php echo ((substr($cdir, 0, strrpos($cdir, '/'))==".")? "":substr($cdir, 0, strrpos($cdir, '/')));?>" title="BACK"><button class="k8-button"><i class="fa-solid fa-right-to-bracket" style="transform:rotate(180deg);"></i></button></a>
<a href="#" title="UPLOAD"><button class="k8-button"><i class="fa-solid fa-upload"></i></button></a>
<a href="#" title="NEW DIRECTORY"><button class="k8-button"><i class="fa-solid fa-folder-plus"></i></button></a>
<a href="#" title="NEW FILE"><button class="k8-button"><i class="fa-solid fa-file-circle-plus"></i></button></a>
</div>
</th>
</tr>

<tr class="k8-green">
<th class='mh'>#</th>
<th colspan="2" style="min-width:256px;">Name</th>
<th >Type</th>
<th >Size</th>
<th >Date Modified</th>
</tr>
<?php
$dirs = glob($directory."*");
foreach($dirs as $dir) {
if(is_dir($dir))
 {
    $filx = pathinfo($dir);
	$name=$filx['filename'];
	$extention=$filx['extension'];
	$time=filemtime($filx['dirname'].'/'.$filx['basename']);
	$modtime= date("F d Y",$time);
	$dirx=explode("/",$dir)[1];
	$sizx=filesize($filx['dirname'].'/'.$filx['basename']);
  $sdatax = array ('name'=>$name,'dir'=>$dirx,'size'=>$sizx,"fdate"=>$modtime);
  
echo ((in_array($name, $blocked))? "" :"<tr><td class='mh'><a href='./?dir=".$filx['dirname'].'/'.$filx['basename']."'><i class='fa-solid fa-folder'></i></a></td>
 <td colspan=2 class='k8-strong'><a class='k8-btn' href='./?dir=".$filx['dirname'].'/'.$filx['basename']."'>".$name." </a></td>
  <td>".(!empty($extention)? $extention: ((is_site($dir))? "Website": "Directory" ))." ".((is_site($dir))? "<a href='".$dir."' target='_blank'><button class='k8-button'><i class='fa-solid fa-arrow-up-right-from-square'></i></button></a>": "" )."</td>
  <td>".pfs_fol($dir)."</td>
  <td>".$modtime."</td>
  </tr>");


}
}
foreach($dirs as $dir) {
if(is_file($dir))
 {
    $filx = pathinfo($dir);
	$name=$filx['filename'];
	$extention=$filx['extension'];
	$time=filemtime($filx['dirname'].'/'.$filx['basename']);
	$modtime= date("F d Y",$time);
	$dirx=explode("/",$dir)[1];
	$sizx=filesize($filx['dirname'].'/'.$filx['basename']);
    $sdatax = array ('name'=>$name,'dir'=>$dirx,'size'=>$sizx,"fdate"=>$modtime);
  
  echo ((in_array($name, $blocked))? "" :"<tr><td class='mh'><a  href='#'><i class='".icon($extention)."'></i></a></td>
 <td colspan=2 class='k8-strong'><a class='k8-btn' href='#'>".$name.".".$extention." </a></td>
  <td>".(!empty($extention)? $extention:'Unknown')."-File</td>
  <td>".pfs($sizx)."</td>
  <td>".$modtime."</td>
  </tr>");


}
}




?>

</table>
    </div>
</body></html>
