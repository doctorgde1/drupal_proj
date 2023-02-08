(function ($, Drupal, drupalSettings) {
  'use strict'
  Drupal.behaviors.CurrencyExchangeRatesBehavior = {
    attach: function (context, settings) {
      if (context != document) {
        return;
      }
      const symbols = drupalSettings.currency_exchange_rates.symbols;
      const currencies_data = drupalSettings.currency_exchange_rates.currencies_data;
      const canvases = Array.from(document.querySelectorAll('#currency-charts'));
      const dates = Object.keys(currencies_data);

      canvases.forEach(canvas => {
        let dataset = [];

        symbols.forEach(symbol => {
          let rates = [];

          dates.forEach(date => {
            rates.push(currencies_data[date].rates[`${symbol}`]);
          });

          let chartRatesSet = {
            label: symbol,
            data: rates,
            tension: 0,
            fill: false,
            borderColor: `rgb(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)})`
          };

          dataset.push(chartRatesSet);
        });

        new Chart(canvas, {
          type: 'line',
          data: {
            labels: dates,
            datasets: dataset
          },
        });
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
