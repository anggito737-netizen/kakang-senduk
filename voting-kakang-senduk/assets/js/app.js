document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-confirm]').forEach(function (element) {
    element.addEventListener('click', function (event) {
      if (!window.confirm(element.getAttribute('data-confirm'))) event.preventDefault();
    });
  });

  var amountInput = document.querySelector('[data-vote-amount]');
  var priceTarget = document.querySelector('[data-vote-price]');
  if (amountInput && priceTarget) {
    var pricePerVote = Number(amountInput.dataset.price || 3500);
    var updatePrice = function () {
      var amount = Math.max(1, Number(amountInput.value) || 1);
      priceTarget.textContent = new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', maximumFractionDigits: 0
      }).format(amount * pricePerVote);
    };
    amountInput.addEventListener('input', updatePrice);
    document.querySelectorAll('[data-set-votes]').forEach(function (button) {
      button.addEventListener('click', function () {
        amountInput.value = button.dataset.setVotes;
        updatePrice();
      });
    });
    updatePrice();
  }
});
