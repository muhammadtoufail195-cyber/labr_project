<?php
$page_title = 'New Receipt';
require __DIR__.'/config.php'; 
require_login();
require __DIR__.'/app/helpers.php';

/* Load patients/tests (lightweight but high limit) */
$patients = $conn->query("SELECT id, name, mr_no, IFNULL(mobile,'') AS mobile FROM patients ORDER BY id DESC LIMIT 800");
$tests    = $conn->query("SELECT id, name, rate FROM tests ORDER BY name ASC");
$PATIENTS = $patients ? $patients->fetch_all(MYSQLI_ASSOC) : [];
$TESTS    = $tests ? $tests->fetch_all(MYSQLI_ASSOC) : [];

include __DIR__.'/app/layout_header.php';
?>

<style>
.card h3{margin-bottom:12px;font-weight:600}
.input{width:100%;padding:12px 14px;font-size:15px;border:1px solid #d1d5db;border-radius:8px;}
.input:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.15);outline:none}
.combo{position:relative}
.combo-list{position:absolute;left:0;right:0;top:calc(100% + 4px);background:#fff;border:1px solid #e5e7eb;
border-radius:10px;max-height:260px;overflow:auto;padding:6px;list-style:none;margin:0;z-index:50;
box-shadow:0 10px 24px rgba(0,0,0,.08);}
.combo-item{padding:8px 10px;border-radius:8px;cursor:pointer;display:flex;justify-content:space-between}
.combo-item:hover,.combo-item.active{background:#eff6ff}
.muted{color:#64748b;font-size:12px}

/* Fast UI Buttons */
.button-31{background:#222;border:0;border-radius:5px;color:#fff;font-weight:600;font-size:15px;
padding:10px 16px;cursor:pointer;transition:.2s;display:inline-block;text-align:center}
.button-31:hover{opacity:.85;transform:translateY(-1px)}
.button-green{background:#16a34a}.button-green:hover{background:#15803d}
.button-blue{background:#2563eb}.button-blue:hover{background:#1d4ed8}
.button-gray{background:#4b5563}.button-gray:hover{background:#374151}

/* Table */
.table th{background:#f9fafb;padding:6px;text-align:left;font-weight:600}
.table td{padding:6px;border-bottom:1px solid #f3f4f6}
.table tr:hover td{background:#f9fafb}
</style>

<div class="card">
  <h3>🧾 New Receipt</h3>
  <form id="receiptForm" method="post" action="<?= BASE_URL ?>/receipt_save.php" autocomplete="off">
    <div class="row">
      <div class="col-6">
        <label>Patient (type name / MR / mobile)</label>
        <div class="combo">
          <input id="patientSearch" class="input" autofocus placeholder="Search patient…">
          <input type="hidden" name="patient_id" id="patient_id" required>
          <ul id="patientList" class="combo-list" hidden></ul>
        </div>
      </div>
      <div class="col-3">
        <label>Discount (Rs)</label>
        <input class="input" type="number" name="discount" id="discount" step="0.01" value="0">
      </div>
      <div class="col-3">
        <label>Paid (Rs)</label>
        <input class="input" type="number" name="paid" id="paid" step="0.01" value="0">
      </div>
    </div>

    <div class="card" style="margin-top:12px">
      <h4>Tests</h4>
      <table class="table" id="items">
        <tr><th>Test</th><th>Rate</th><th>Qty</th><th>Amount</th><th></th></tr>
      </table>

      <div class="row">
        <div class="col-6">
          <div class="combo">
            <input id="testSearch" class="input" placeholder="Search test name…">
            <ul id="testList" class="combo-list" hidden></ul>
          </div>
        </div>
        <div class="col-3">
          <input id="qty" class="input" type="number" min="1" value="1" title="Qty">
        </div>
        <div class="col-3">
          <button type="button" id="addBtn" class="button-31 button-blue" style="width:100%">Add</button>
        </div>
      </div>

      <input type="hidden" name="items_json" id="items_json">
      <input type="hidden" name="print_after_save" value="1">

      <div style="text-align:right;margin-top:10px;font-size:15px">
        <div><b>Subtotal:</b> Rs <span id="subtotal">0.00</span></div>
        <div><b>Grand Total:</b> Rs <span id="grand">0.00</span></div>
      </div>
    </div>

    <button class="button-31 button-green" style="width:100%;margin-top:12px;font-size:16px;">
      💾 Save & Print
    </button>
  </form>
</div>

<script>
const PATIENTS = <?= json_encode($PATIENTS) ?>;
const TESTS    = <?= json_encode($TESTS) ?>;
const $ = id=>document.getElementById(id);
const norm=s=>(s||'').toLowerCase();
const esc=s=>(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
const debounce=(fn,ms=150)=>{let t;return(...a)=>{clearTimeout(t);t=setTimeout(()=>fn(...a),ms)}};

/* === Patient Search === */
const pSearch=$('patientSearch'), pList=$('patientList'), pId=$('patient_id');
function showPatients(q){
  const term=norm(q);
  const rows=PATIENTS.filter(p=>(p.name+' '+(p.mr_no||'')+' '+(p.mobile||'')).toLowerCase().includes(term)).slice(0,50);
  pList.innerHTML=rows.map(p=>`<li class="combo-item" data-id="${p.id}" data-label="${esc(p.name)} (MR:${esc(p.mr_no||'-')})">
    <span>${esc(p.name)}</span><span class="muted">MR:${esc(p.mr_no||'-')} · ${esc(p.mobile||'')}</span></li>`).join('')||(term?'<li class="muted combo-item">No match</li>':'');
  pList.hidden=false;
}
pSearch.addEventListener('input',debounce(e=>showPatients(e.target.value)));
pList.addEventListener('click',e=>{
  const li=e.target.closest('.combo-item');if(!li||!li.dataset.id)return;
  pId.value=li.dataset.id;pSearch.value=li.dataset.label;pList.hidden=true;
});
document.addEventListener('click',e=>{if(!pList.contains(e.target)&&e.target!==pSearch)pList.hidden=true});

/* === Test Search === */
const tSearch=$('testSearch'), tList=$('testList'), qty=$('qty');
function showTests(q){
  const term=norm(q);
  const rows=TESTS.filter(t=>t.name.toLowerCase().includes(term)).slice(0,80);
  tList.innerHTML=rows.map(t=>`<li class="combo-item" data-id="${t.id}" data-name="${esc(t.name)}" data-rate="${t.rate}">
    <span>${esc(t.name)}</span><span class="muted">Rs ${t.rate}</span></li>`).join('')||(term?'<li class="muted combo-item">No match</li>':'');
  tList.hidden=false;
}
tSearch.addEventListener('input',debounce(e=>showTests(e.target.value)));
tList.addEventListener('click',e=>{
  const li=e.target.closest('.combo-item');if(!li||!li.dataset.id)return;
  addItem({id:+li.dataset.id,name:li.dataset.name,rate:+li.dataset.rate,qty:+qty.value||1});
  tList.hidden=true;tSearch.value='';qty.value=1;
});
document.addEventListener('click',e=>{if(!tList.contains(e.target)&&e.target!==tSearch)tList.hidden=true});

/* === Items === */
let items=[];
function render(){
  const tb=$('items');tb.innerHTML='<tr><th>Test</th><th>Rate</th><th>Qty</th><th>Amt</th><th></th></tr>';
  let sub=0;items.forEach((it,i)=>{const amt=it.rate*it.qty;sub+=amt;
    tb.insertAdjacentHTML('beforeend',`<tr><td>${esc(it.name)}</td><td>${it.rate.toFixed(2)}</td><td>${it.qty}</td><td>${amt.toFixed(2)}</td>
    <td><button type="button" class="button-31 button-gray" onclick="delItem(${i})">X</button></td></tr>`);});
  $('subtotal').textContent=sub.toFixed(2);
  const disc=parseFloat($('discount').value||0);
  $('grand').textContent=(sub-disc).toFixed(2);
  $('items_json').value=JSON.stringify(items);
}
function addItem(it){const i=items.findIndex(x=>x.id==it.id);if(i>=0)items[i].qty+=it.qty;else items.push(it);render();}
function delItem(i){items.splice(i,1);render();}
$('discount').addEventListener('input',render);
$('addBtn').addEventListener('click',()=>{const first=tList.querySelector('.combo-item[data-id]');if(first)first.click();else showTests(tSearch.value);});
render();

/* === Submit === */
$('receiptForm').addEventListener('submit',e=>{
  render();
  if(!pId.value){e.preventDefault();alert('Select a patient first.');pSearch.focus();return;}
  if(!items.length){e.preventDefault();alert('Add at least one test.');tSearch.focus();return;}
});
</script>

<?php include __DIR__.'/app/layout_footer.php'; ?>
