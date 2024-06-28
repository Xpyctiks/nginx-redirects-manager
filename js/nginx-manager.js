function checkAll(bx) {
  var cbs = document.getElementsByTagName('input');
  for(var i=0; i < cbs.length; i++) {
    if(cbs[i].type == 'checkbox') {
      cbs[i].checked = bx.checked;
    }
  }
}

window.onload = function() {
  window.$('#modalRecordsAddError').modal('show');
  window.$('#modalCommit').modal('show');
}

function showLoading() {
  document.getElementById("spinner").style.visibility = "visible";
}

function deleteLoading() {
  document.getElementById("spinnerLoading").className = "spinner-border text-danger";
  document.getElementById("spinnerLoading").style.visibility = "visible";
}
