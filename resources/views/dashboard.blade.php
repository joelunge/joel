@extends('layouts.default')

@section('title', 'Trades')

@section('content')

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">Profits</div>

            <div class="card-body">
                <div id="chart-profits"></div>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    Highcharts.chart('chart-profits', {

      title: {
        text: 'Profits'
      },

      subtitle: {
        text: 'USD by trade'
      },

      yAxis: {
        title: {
          text: ''
        }
      },
      xAxis: {
        labels: {
          enabled: false
        }
      },
      legend: {
        layout: 'vertical',
        align: 'right',
        verticalAlign: 'middle'
      },

      plotOptions: {
        line: {
          marker: {
            enabled: true
          }
        },
        series: {
          label: {
            connectorAllowed: false
          }
        }
      },

      series: [{
        name: 'Joel',
        data: [
            @foreach ($results as $result)
                {{round($result)}},
            @endforeach
        ]
      }],

      responsive: {
        rules: [{
          condition: {
            maxWidth: 500
          },
          chartOptions: {
            legend: {
              layout: 'horizontal',
              align: 'center',
              verticalAlign: 'bottom'
            }
          }
        }]
      }

    });
});
</script>


@endsection