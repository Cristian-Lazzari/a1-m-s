
@extends('layouts.base')

@section('contents')
@vite(['resources/js/app.js'])

    <div class="stat">
        <h1>Statistiche</h1>

        <h2>Prodotti più ordinati</h2>
        <div class="chart">
            <canvas class="graph" id="topProductsChart"></canvas>
            <div class="list">
                <table class=" table table mytable"> 
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Prodotto</th>
                            <th scope="col">Quantità</th>
                        </tr>
                    </thead>
                    @php $index = 1 @endphp
                    @foreach ($topProducts as $key => $value)
                    <tr>
                        <td>{{$index}}</td>
                        <td>{{$key}}</td>
                        <td><strong>{{$value}}</strong></td>
                    </tr>
                    @php $index ++ @endphp
                    @endforeach
                </table>
            </div>
        </div>

        <h2>Come vengono ordinati i prodotti nel tempo</h2>
        <div class="chart">
            <canvas class="graph" id="ordersOverTimeChart"></canvas>
            <div class="list">
                <table class="mytable table table-striped "> 
                    <thead>
                        <tr>
                            <th scope="col">Prodotto</th>
                            <th scope="col">Data</th>
                            <th scope="col">Quantità</th>
                        </tr>
                    </thead>
                    @foreach ($ordersOverTime as $key => $value)
                    <tr>
                        <td>{{$value->name}}</td>
                        <td>{{$value->day}}</td>
                        <td>{{$value->quantity}}</td>
                       
                    </tr>
                    @endforeach
                </table>
            </div>
        </div>

        <h2>Ricavi nel tempo</h2>
        <div class="chart">
            <canvas class="graph" id="revenueOverTimeChart"></canvas>
            <div class="list">
                <table class="mytable table table-striped "> 
                    <thead>
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Data</th>
                            <th scope="col">Fatturato dal sito con ordini</th>
                        </tr>
                    </thead>
                    @php $index = 1 @endphp
                    @foreach ($revenueOverTime as $key => $value)
                    <tr>
                        <td>{{$index}}</td>
                        <td>{{$key}}</td>
                        <td><strong>€{{$value / 100}}</strong></td>
                    </tr>
                    @php $index ++ @endphp
                    @endforeach
                </table>
            </div>
        </div>
        <h2>Prenotazioni ai tavoli nel tempo</h2>
        <div class="chart">
            <canvas class="graph" id="reservationsOverTimeChart"></canvas>

            <div class="list">
                <table class="mytable table table-striped "> 
                    <thead>
                        <tr>
                            
                            <th scope="col">Data</th>
                            <th scope="col">Adulti</th>
                            <th scope="col">Bambini</th>
                        </tr>
                    </thead>
                    @foreach ($reservationsOverTime as $key => $value)
                    <tr>
                        <td>{{$value->formatted_date}}</td>
                        <td>{{$value->total_adults}}</td>
                        <td>{{$value->total_children}}</td>
                    </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
@endsection

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3"></script>
<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function() {
        const colors = [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
            '#FF9F40', '#66FF66', '#FF6633', '#0099FF', '#CC33FF',
            '#FF6699', '#66CCFF', '#CCFF33', '#FF9933', '#CC66CC',
            '#FFCC66', '#3366FF', '#33CC99', '#FF6666', '#66FFCC'
        ];

        // Grafico a torta per i prodotti più ordinati
        const topProductsData = @json($topProducts);
        new Chart(document.getElementById('topProductsChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: Object.keys(topProductsData),
                datasets: [{
                    data: Object.values(topProductsData),
                    backgroundColor: colors,
                }]
            },
            options: {
                responsive: true,
            }
        });

        // Grafico a colonne per le ordinazioni nel tempo
        const ordersOverTime = @json($ordersOverTime);
        const columnsLabels = [...new Set(ordersOverTime.map(item => item.day))]; // Usa il giorno come label
        const products = [...new Set(ordersOverTime.map(item => item.name))];

        let i = -1;
        const productDatasets = products.map(product => {
            i++;
            return {
                label: product,
                data: columnsLabels.map(day => {
                    const item = ordersOverTime.find(e => e.day === day && e.name === product);
                    return item ? item.quantity : 0;
                }),
                backgroundColor: colors[i],
            };
        });

        new Chart(document.getElementById('ordersOverTimeChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: columnsLabels,
                datasets: productDatasets,
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Grafico a linee per i ricavi nel tempo
        const revenueOverTime = @json($revenueOverTime);
        const revenueInEuros = Object.values(revenueOverTime).map(value => value / 100);
        new Chart(document.getElementById('revenueOverTimeChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: Object.keys(revenueOverTime),
                datasets: [{
                    label: 'Ricavi totali',
                    data: revenueInEuros, // Usa i valori convertiti in euro
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Ricavi in €'
                        },
                    },
                }
            }
        });
        // const reservationsData = @json($reservationsOverTime);
    
        // const days = [...new Set(reservationsData.map(item => item.day))];
        // const hours = [...new Set(reservationsData.map(item => item.hour))];
        
        // const adultsData = days.flatMap(day => hours.map(hour => {
        //     const reservation = reservationsData.find(item => item.day === day && item.hour === hour);
        //     return reservation ? reservation.total_adults : 0;
        // }));

        // const childrenData = days.flatMap(day => hours.map(hour => {
        //     const reservation = reservationsData.find(item => item.day === day && item.hour === hour);
        //     return reservation ? reservation.total_children : 0;
        // }));

        // new Chart(document.getElementById('reservationsOverTimeChart').getContext('2d'), {
        //     type: 'bar',
        //     data: {
        //         labels: days.map((day, index) => `${day} ${hours[index % hours.length]}:00`),
        //         datasets: [
        //             {
        //                 label: 'Adulti',
        //                 data: adultsData,
        //                 backgroundColor: 'rgba(54, 162, 235, 0.7)',
        //             },
        //             {
        //                 label: 'Bambini',
        //                 data: childrenData,
        //                 backgroundColor: 'rgba(255, 99, 132, 0.7)',
        //             }
        //         ]
        //     },
        //     options: {
        //         responsive: true,
        //         scales: {
        //             x: {
        //                 type: 'time',
        //                 time: {
        //                     unit: 'day',
        //                     displayFormats: {
        //                         hour: 'MMM dd, H:mm' // Modificato "DD" in "dd"
        //                     }
        //                 },
        //                 title: {
        //                     display: true,
        //                     text: 'Data'
        //                 },
        //                 ticks: {
        //                     maxRotation: 45,
        //                     minRotation: 45
        //                 }
        //             },
        //             y: {
        //                 beginAtZero: true,
        //                 title: {
        //                     display: true,
        //                     text: 'Numero di persone'
        //                 },
        //                 ticks: {
        //                     stepSize: 1,
        //                     callback: function(value) { return Number.isInteger(value) ? value : ''; }
        //                 }
        //             }
        //         }
        //     }

        // });
        const labels = @json($reservationsOverTime->pluck('formatted_date'));
        const totalAdults = @json($reservationsOverTime->pluck('total_adults'));
        const totalChildren = @json($reservationsOverTime->pluck('total_children'));

        const ctx = document.getElementById('reservationsOverTimeChart').getContext('2d');
        const myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Adulti',
                        data: totalAdults,
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1,
                    },
                    {
                        label: 'Bambini',
                        data: totalChildren,
                        backgroundColor: 'rgba(255, 99, 132, 0.6)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                    },
                ],
            },
            options: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Numero di persone'
                    },
                    ticks: {
                        stepSize: 1,
                        callback: function(value) { return Number.isInteger(value) ? value : ''; }
                    }
                }
            },
        });

    });
</script>


