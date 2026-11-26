<style>
*{box-sizing:border-box}
body{margin:0;font:14px/1.35 system-ui,sans-serif}
#advent{position:relative;overflow:hidden;margin:auto;max-width:440px;padding:18px 15px 14px;text-align:center;color:#ffedca;border:1px solid #bf8065;border-radius:21px;background:radial-gradient(ellipse at 50% 0%,#ac454c,transparent 56%),linear-gradient(145deg,#88263b,#541025)}
#advent:before{content:'';position:absolute;inset:5px;border:1px solid #e6bc7333;border-radius:16px;pointer-events:none}
.garland{position:absolute;top:0;left:0;width:100%;height:68px;pointer-events:none}
header,.stats,#days,button{position:relative}
small{font-size:9px;font-weight:700;letter-spacing:.17em;color:#efd08e}
h1,h2{font:35px/1.08 serif;margin:6px 0 16px}
em{color:#efca86}
.stats{display:flex;justify-content:space-between;font-size:11px}
.track{height:4px;border-radius:5px;margin:6px 0 12px;background:#450f24;overflow:hidden}
.track i{height:100%;display:block;background:#ecc582}
#days{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:4px}
button{cursor:pointer;font:inherit}
button[disabled]{cursor:default;opacity:.65}
.day{display:flex;justify-content:center;align-items:center;flex-direction:column;height:39px;padding:0;border:1px solid #edc38c33;border-radius:7px;background:#ffffff09;color:#efd7ad;font:17px/1 serif}
.day span{font:10px/1 sans-serif;margin-top:2px}
.day.available,.gold{background:linear-gradient(120deg,#ffe1aa,#d9a25d);color:#632437}
.day.empty{color:#c69e9c;border-color:transparent}
.day.done{background:#421626}
.day.last{border-color:#edc67d}
.legend,#notice{font-size:11px;color:#ecc9b8;margin:9px 0 0}
.gift{display:block;margin:auto;width:74%;max-width:200px;height:103px}
.spark{opacity:0}
.open .lid{transform:translate(20px,-35px) rotate(12deg);transform-origin:140px 78px}
.open .spark{opacity:1}
.fresh .lid{animation:lift .8s both}
.fresh .spark{animation:burst .6s .26s both}
.fresh #letter{animation:burst .35s .46s both}
#letter{font:21px/1.18 serif;overflow-wrap:anywhere;white-space:pre-wrap;margin:8px auto 15px;color:#ffe7b5}
.gold{border:0;border-radius:11px;padding:10px 15px;min-height:40px;font-weight:700}
#answer{position:relative}
.secondary{color:#ffdaa2;background:transparent;border:0;margin:5px;padding:6px}
#front p{margin:6px 0 12px}
.morning h1{margin-bottom:5px}
#advent.welcome{padding-bottom:calc(18px + var(--tg-content-safe-area-inset-bottom,0px))}
.welcome p{position:relative;margin:12px 0 18px}
.welcome .gold{position:relative;display:flex;align-items:center;justify-content:center;min-height:44px;text-decoration:none}
.welcome .gold:focus-visible{outline:2px solid #ffedca;outline-offset:3px}
@keyframes lift{0%{transform:none}25%{transform:rotate(-7deg)}40%{transform:rotate(6deg)}to{transform:translate(20px,-35px) rotate(12deg)}}
@keyframes burst{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:none}}
@media(max-width:275px){#advent{padding:14px 9px}#days{gap:2px}.day{font-size:14px;height:36px}h1{font-size:27px}#letter{font-size:17px}}
@media(prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}}
</style>
