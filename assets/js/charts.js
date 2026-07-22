/**
 * LeafLink Data Visualization Library
 * Charts and visualization utilities using Chart.js
 */


const LEAFLINK_COLORS = {

    primary: '#0bca44',
    secondary: '#f4a261',
    accent: '#e76f51',
    light_green: '#b8d4b0',
    text: '#137813',
    light_bg: '#f0f5f1',
    warning: '#ff6b6b',
    success: '#51cf66',
    info: '#4dabf7'

};


/*
|--------------------------------------------------------------------------
| Production Trend Chart
|--------------------------------------------------------------------------
*/


function createProductionTrendChart(canvasId, labels, data) {


    const ctx = document
        .getElementById(canvasId)
        .getContext('2d');


    return new Chart(ctx, {


        type: 'line',


        data: {


            labels: labels,


            datasets: [{


                label: 'Production (kg)',

                data: data,


                borderColor: LEAFLINK_COLORS.primary,

                backgroundColor:
                'rgba(11,202,68,0.1)',


                borderWidth: 3,

                fill:true,

                tension:0.4,


                pointRadius:5,


                pointBackgroundColor:
                LEAFLINK_COLORS.primary


            }]


        },


        options:{


            responsive:true,


            scales:{


                y:{


                    beginAtZero:true,


                    title:{


                        display:true,

                        text:'Kilograms (kg)'


                    }

                }


            }


        }


    });


}





/*
|--------------------------------------------------------------------------
| Sales and Revenue Trend
|--------------------------------------------------------------------------
*/


function createSalesAndRevenueTrendChart(
    canvasId,
    labels,
    kgData,
    revenueData
){


    const ctx =
    document.getElementById(canvasId)
    .getContext('2d');



    return new Chart(ctx,{


        type:'line',


        data:{


            labels:labels,


            datasets:[


                {


                    label:'Kilograms (kg)',

                    data:kgData,


                    borderColor:
                    LEAFLINK_COLORS.primary,


                    tension:0.3


                },


                {


                    label:'Revenue ($)',

                    data:revenueData,


                    borderColor:
                    LEAFLINK_COLORS.info,


                    tension:0.3


                }


            ]


        },


        options:{


            responsive:true


        }


    });


}

/*
|--------------------------------------------------------------------------
| Revenue vs Debt Impact Chart
|--------------------------------------------------------------------------
*/


function createRevenueDebtChart(canvasId, labels, values){


    const ctx =
    document.getElementById(canvasId)
    .getContext('2d');



    return new Chart(ctx,{


        type:'bar',


        data:{


            labels:labels,


            datasets:[{


                label:'Amount (USD)',


                data:values,


                backgroundColor:[

                    LEAFLINK_COLORS.info,

                    LEAFLINK_COLORS.warning,

                    LEAFLINK_COLORS.primary

                ]


            }]


        },


        options:{


            responsive:true,


            plugins:{


                legend:{


                    display:false


                }


            },


            scales:{


                y:{


                    beginAtZero:true,


                    title:{


                        display:true,

                        text:'USD'


                    }


                }


            }


        }


    });


}





/*
|--------------------------------------------------------------------------
| Revenue Performance Chart
|--------------------------------------------------------------------------
*/


function createRevenueChart(
    canvasId,
    labels,
    projectedData,
    actualData
){


    const ctx =
    document.getElementById(canvasId)
    .getContext('2d');



    return new Chart(ctx,{


        type:'bar',


        data:{


            labels:labels,


            datasets:[


                {


                    label:'Projected Revenue',


                    data:projectedData,


                    backgroundColor:
                    LEAFLINK_COLORS.light_green


                },


                {


                    label:'Actual Revenue',


                    data:actualData,


                    backgroundColor:
                    LEAFLINK_COLORS.primary


                }


            ]


        },


        options:{


            responsive:true,


            scales:{


                y:{


                    beginAtZero:true,


                    title:{


                        display:true,

                        text:'Revenue ($)'


                    }


                }


            }


        }


    });


}





/*
|--------------------------------------------------------------------------
| Quality Distribution Chart
|--------------------------------------------------------------------------
*/


function createQualityDistributionChart(
    canvasId,
    grades,
    counts
){


    const ctx =
    document.getElementById(canvasId)
    .getContext('2d');



    return new Chart(ctx,{


        type:'doughnut',


        data:{


            labels:grades,


            datasets:[{


                data:counts,


                backgroundColor:[

                    LEAFLINK_COLORS.primary,

                    LEAFLINK_COLORS.secondary,

                    LEAFLINK_COLORS.accent,

                    LEAFLINK_COLORS.light_green,

                    LEAFLINK_COLORS.info

                ]


            }]


        },


        options:{


            responsive:true,


            plugins:{


                legend:{


                    position:'bottom'


                }


            }


        }


    });


}

/*
|--------------------------------------------------------------------------
| Grower Performance Comparison Chart
|--------------------------------------------------------------------------
*/


function createGrowerComparisonChart(
    canvasId,
    growerNames,
    productionData,
    revenueData
){


    const ctx =
    document.getElementById(canvasId)
    .getContext('2d');



    return new Chart(ctx,{


        type:'bar',


        data:{


            labels:growerNames,


            datasets:[


                {


                    label:'Production (kg)',


                    data:productionData,


                    backgroundColor:
                    LEAFLINK_COLORS.primary


                },


                {


                    label:'Revenue ($)',


                    data:revenueData,


                    backgroundColor:
                    LEAFLINK_COLORS.secondary


                }


            ]


        },


        options:{


            responsive:true,


            scales:{


                y:{


                    beginAtZero:true


                }


            }


        }


    });


}





/*
|--------------------------------------------------------------------------
| Debt Status Chart
|--------------------------------------------------------------------------
*/


function createDebtStatusChart(
    canvasId,
    growerNames,
    debtAmounts
){


    const ctx =
    document.getElementById(canvasId)
    .getContext('2d');



    return new Chart(ctx,{


        type:'bar',


        data:{


            labels:growerNames,


            datasets:[{


                label:'Total Debt ($)',


                data:debtAmounts,


                backgroundColor:
                LEAFLINK_COLORS.warning


            }]


        },


        options:{


            indexAxis:'y',


            responsive:true,


            scales:{


                x:{


                    beginAtZero:true


                }


            }


        }


    });


}





/*
|--------------------------------------------------------------------------
| Contract Status Chart
|--------------------------------------------------------------------------
*/


function createContractStatusChart(
    canvasId,
    statusCounts
){


    const ctx =
    document.getElementById(canvasId)
    .getContext('2d');



    return new Chart(ctx,{


        type:'pie',


        data:{


            labels:[

                'Active',

                'Completed',

                'Cancelled'

            ],


            datasets:[{


                data:[

                    statusCounts.active || 0,

                    statusCounts.completed || 0,

                    statusCounts.cancelled || 0

                ],


                backgroundColor:[

                    LEAFLINK_COLORS.primary,

                    LEAFLINK_COLORS.light_green,

                    LEAFLINK_COLORS.warning

                ]


            }]


        },


        options:{


            responsive:true


        }


    });


}





/*
|--------------------------------------------------------------------------
| Regional Performance Data Formatter
|--------------------------------------------------------------------------
*/


function createRegionalPerformanceMatrix(regionData){


    return {


        regions:
        regionData.map(r=>r.region),


        growerCounts:
        regionData.map(r=>r.growers),


        production:
        regionData.map(r=>r.production),


        revenue:
        regionData.map(r=>r.revenue)


    };


}

window.LeafLinkCharts = {
    createProductionTrendChart,
    createRevenueChart,
}

/*
|--------------------------------------------------------------------------
| Metrics Card Helper
|--------------------------------------------------------------------------
*/


function createMetricsCard(title, value, unit, trend = null){


    let trendHTML = "";


    if(trend !== null){


        trendHTML = `

            <p style="
            color:${trend >= 0 ? 
            LEAFLINK_COLORS.success : 
            LEAFLINK_COLORS.warning};

            font-weight:bold;">

            ${trend >= 0 ? '↑' : '↓'}
            ${Math.abs(trend)}%

            </p>

        `;

    }



    return `


        <div class="metric-card">


            <h3>${title}</h3>


            <p class="metric-value">
            ${Number(value).toLocaleString()}
            </p>


            <p class="metric-unit">
            ${unit}
            </p>


            ${trendHTML}


        </div>


    `;


}





/*
|--------------------------------------------------------------------------
| Export Charts To Dashboard
|--------------------------------------------------------------------------
*/


window.LeafLinkCharts = {


    colors: LEAFLINK_COLORS,


    createProductionTrendChart,


    createSalesAndRevenueTrendChart,


    createRevenueDebtChart,


    createRevenueChart,


    createQualityDistributionChart,


    createGrowerComparisonChart,


    createDebtStatusChart,


    createContractStatusChart,


    createRegionalPerformanceMatrix,


    createMetricsCard


};
