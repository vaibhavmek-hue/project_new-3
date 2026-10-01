/**
 * Dashboard Analytics
 */

'use strict';

(function () {
  let cardColor, headingColor, axisColor, shadeColor, borderColor;
  let growthChart, profileReportChart, statisticsChart, incomeChart, weeklyExpenses;

  cardColor = config.colors.white;
  headingColor = config.colors.headingColor;
  axisColor = config.colors.axisColor;
  borderColor = config.colors.borderColor;

  // Monthly Projects Chart - Bar Chart (LIVE data from projects table)
  // --------------------------------------------------------------------
  const totalRevenueChartEl = document.querySelector('#totalRevenueChart'),
    totalRevenueChartOptions = {
      series: [
        {
          name: 'Completed',
          data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
        },
        {
          name: 'In Progress',
          data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
        }
      ],
      chart: {
        height: 300,
        stacked: true,
        type: 'bar',
        toolbar: { show: false }
      },
      plotOptions: {
        bar: {
          horizontal: false,
          columnWidth: '33%',
          borderRadius: 12,
          startingShape: 'rounded',
          endingShape: 'rounded'
        }
      },
      colors: [config.colors.primary, config.colors.info],
      dataLabels: {
        enabled: false
      },
      stroke: {
        curve: 'smooth',
        width: 6,
        lineCap: 'round',
        colors: [cardColor]
      },
      legend: {
        show: true,
        horizontalAlign: 'left',
        position: 'top',
        markers: {
          height: 8,
          width: 8,
          radius: 12,
          offsetX: -3
        },
        labels: {
          colors: axisColor
        },
        itemMargin: {
          horizontal: 10
        }
      },
      grid: {
        borderColor: borderColor,
        padding: {
          top: 0,
          bottom: -8,
          left: 20,
          right: 20
        }
      },
      xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        labels: {
          style: {
            fontSize: '13px',
            colors: axisColor
          }
        },
        axisTicks: {
          show: false
        },
        axisBorder: {
          show: false
        }
      },
      yaxis: {
        labels: {
          style: {
            fontSize: '13px',
            colors: axisColor
          }
        }
      },
      responsive: [
        {
          breakpoint: 1700,
          options: {
            plotOptions: {
              bar: {
                borderRadius: 10,
                columnWidth: '32%'
              }
            }
          }
        },
        {
          breakpoint: 1580,
          options: {
            plotOptions: {
              bar: {
                borderRadius: 10,
                columnWidth: '35%'
              }
            }
          }
        },
        {
          breakpoint: 1440,
          options: {
            plotOptions: {
              bar: {
                borderRadius: 10,
                columnWidth: '42%'
              }
            }
          }
        },
        {
          breakpoint: 1300,
          options: {
            plotOptions: {
              bar: {
                borderRadius: 10,
                columnWidth: '48%'
              }
            }
          }
        },
        {
          breakpoint: 1200,
          options: {
            plotOptions: {
              bar: {
                borderRadius: 10,
                columnWidth: '40%'
              }
            }
          }
        },
        {
          breakpoint: 1040,
          options: {
            plotOptions: {
              bar: {
                borderRadius: 11,
                columnWidth: '48%'
              }
            }
          }
        },
        {
          breakpoint: 991,
          options: {
            plotOptions: {
              bar: {
                borderRadius: 10,
                columnWidth: '30%'
              }
            }
          }
        },
        {
          breakpoint: 840,
          options: {
            plotOptions: {
              bar: {
                borderRadius: 10,
                columnWidth: '35%'
              }
            }
          }
        },
        {
          breakpoint: 768,
          options: {
            plotOptions: {
              bar: {
                borderRadius: 10,
                columnWidth: '28%'
              }
            }
          }
        },
        {
          breakpoint: 640,
          options: {
            plotOptions: {
              bar: {
                borderRadius: 10,
                columnWidth: '32%'
              }
            }
          }
        },
        {
          breakpoint: 576,
          options: {
            plotOptions: {
              bar: {
                borderRadius: 10,
                columnWidth: '37%'
              }
            }
          }
        },
        {
          breakpoint: 480,
          options: {
            plotOptions: {
              bar: {
                borderRadius: 10,
                columnWidth: '45%'
              }
            }
          }
        },
        {
          breakpoint: 420,
          options: {
            plotOptions: {
              bar: {
                borderRadius: 10,
                columnWidth: '52%'
              }
            }
          }
        },
        {
          breakpoint: 380,
          options: {
            plotOptions: {
              bar: {
                borderRadius: 10,
                columnWidth: '60%'
              }
            }
          }
        }
      ],
      states: {
        hover: {
          filter: {
            type: 'none'
          }
        },
        active: {
          filter: {
            type: 'none'
          }
        }
      }
    };
  if (typeof totalRevenueChartEl !== 'undefined' && totalRevenueChartEl !== null) {
    const totalRevenueChart = new ApexCharts(totalRevenueChartEl, totalRevenueChartOptions);
    totalRevenueChart.render();

    // Pull real project counts from the database and push them into the chart.
    const loadTotalRevenueChartData = function () {
      fetch('chart_data.php?year=' + new Date().getFullYear())
        .then(function (res) {
          return res.json();
        })
        .then(function (data) {
          totalRevenueChart.updateOptions({
            xaxis: { categories: data.categories }
          });
          totalRevenueChart.updateSeries([
            { name: 'Completed', data: data.completed },
            { name: 'In Progress', data: data.inprogress }
          ]);
        })
        .catch(function (err) {
          console.error('Failed to load live project chart data:', err);
        });
    };

    loadTotalRevenueChartData();
    // Refresh every 30s so the chart stays live as projects are added/updated.
    setInterval(loadTotalRevenueChartData, 30000);
  }

  // Growth Chart - Radial Bar Chart
  // --------------------------------------------------------------------
  const growthChartEl = document.querySelector('#growthChart'),
    growthChartOptions = {
      series: [0],
      labels: ['Completed'],
      chart: {
        height: 240,
        type: 'radialBar'
      },
      plotOptions: {
        radialBar: {
          size: 150,
          offsetY: 10,
          startAngle: -150,
          endAngle: 150,
          hollow: {
            size: '55%'
          },
          track: {
            background: cardColor,
            strokeWidth: '100%'
          },
          dataLabels: {
            name: {
              offsetY: 15,
              color: headingColor,
              fontSize: '15px',
              fontWeight: '600',
              fontFamily: 'Public Sans'
            },
            value: {
              offsetY: -25,
              color: headingColor,
              fontSize: '22px',
              fontWeight: '500',
              fontFamily: 'Public Sans'
            }
          }
        }
      },
      colors: [config.colors.primary],
      fill: {
        type: 'gradient',
        gradient: {
          shade: 'dark',
          shadeIntensity: 0.5,
          gradientToColors: [config.colors.primary],
          inverseColors: true,
          opacityFrom: 1,
          opacityTo: 0.6,
          stops: [30, 70, 100]
        }
      },
      stroke: {
        dashArray: 5
      },
      grid: {
        padding: {
          top: -35,
          bottom: -10
        }
      },
      states: {
        hover: {
          filter: {
            type: 'none'
          }
        },
        active: {
          filter: {
            type: 'none'
          }
        }
      }
    };
  if (typeof growthChartEl !== 'undefined' && growthChartEl !== null) {
    growthChart = new ApexCharts(growthChartEl, growthChartOptions);
    growthChart.render();
  }

  // Profit Report Line Chart
  // --------------------------------------------------------------------
  const profileReportChartEl = document.querySelector('#profileReportChart'),
    profileReportChartConfig = {
      chart: {
        height: 80,
        // width: 175,
        type: 'line',
        toolbar: {
          show: false
        },
        dropShadow: {
          enabled: true,
          top: 10,
          left: 5,
          blur: 3,
          color: config.colors.warning,
          opacity: 0.15
        },
        sparkline: {
          enabled: true
        }
      },
      grid: {
        show: false,
        padding: {
          right: 8
        }
      },
      colors: [config.colors.warning],
      dataLabels: {
        enabled: false
      },
      stroke: {
        width: 5,
        curve: 'smooth'
      },
      series: [
        {
          data: [0, 0, 0, 0, 0, 0]
        }
      ],
      xaxis: {
        show: false,
        lines: {
          show: false
        },
        labels: {
          show: false
        },
        axisBorder: {
          show: false
        }
      },
      yaxis: {
        show: false
      }
    };
  if (typeof profileReportChartEl !== 'undefined' && profileReportChartEl !== null) {
    profileReportChart = new ApexCharts(profileReportChartEl, profileReportChartConfig);
    profileReportChart.render();
  }

  // Order Statistics Chart
  // --------------------------------------------------------------------
  const chartOrderStatistics = document.querySelector('#orderStatisticsChart'),
    orderChartConfig = {
      chart: {
        height: 165,
        width: 130,
        type: 'donut'
      },
      labels: ['Website Work', 'App Work', 'Dashboard Work', 'Technologies'],
      series: [0, 0, 0, 0],
      colors: [config.colors.primary, config.colors.secondary, config.colors.info, config.colors.success],
      stroke: {
        width: 5,
        colors: cardColor
      },
      dataLabels: {
        enabled: false,
        formatter: function (val, opt) {
          return parseInt(val) + '%';
        }
      },
      legend: {
        show: false
      },
      grid: {
        padding: {
          top: 0,
          bottom: 0,
          right: 15
        }
      },
      plotOptions: {
        pie: {
          donut: {
            size: '75%',
            labels: {
              show: true,
              value: {
                fontSize: '1.5rem',
                fontFamily: 'Public Sans',
                color: headingColor,
                offsetY: -15,
                formatter: function (val) {
                  return parseInt(val) + '%';
                }
              },
              name: {
                offsetY: 20,
                fontFamily: 'Public Sans'
              },
              total: {
                show: true,
                fontSize: '0.8125rem',
                color: axisColor,
                label: 'Total',
                formatter: function (w) {
                  return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                }
              }
            }
          }
        }
      }
    };
  if (typeof chartOrderStatistics !== 'undefined' && chartOrderStatistics !== null) {
    statisticsChart = new ApexCharts(chartOrderStatistics, orderChartConfig);
    statisticsChart.render();
  }

  // Income Chart - Area chart
  // --------------------------------------------------------------------
  const incomeChartEl = document.querySelector('#incomeChart'),
    incomeChartConfig = {
      series: [
        {
          data: [0, 0, 0, 0, 0, 0, 0, 0]
        }
      ],
      chart: {
        height: 215,
        parentHeightOffset: 0,
        parentWidthOffset: 0,
        toolbar: {
          show: false
        },
        type: 'area'
      },
      dataLabels: {
        enabled: false
      },
      stroke: {
        width: 2,
        curve: 'smooth'
      },
      legend: {
        show: false
      },
      markers: {
        size: 6,
        colors: 'transparent',
        strokeColors: 'transparent',
        strokeWidth: 4,
        discrete: [
          {
            fillColor: config.colors.white,
            seriesIndex: 0,
            dataPointIndex: 7,
            strokeColor: config.colors.primary,
            strokeWidth: 2,
            size: 6,
            radius: 8
          }
        ],
        hover: {
          size: 7
        }
      },
      colors: [config.colors.primary],
      fill: {
        type: 'gradient',
        gradient: {
          shade: shadeColor,
          shadeIntensity: 0.6,
          opacityFrom: 0.5,
          opacityTo: 0.25,
          stops: [0, 95, 100]
        }
      },
      grid: {
        borderColor: borderColor,
        strokeDashArray: 3,
        padding: {
          top: -20,
          bottom: -8,
          left: -10,
          right: 8
        }
      },
      xaxis: {
        categories: ['', '', '', '', '', '', '', ''],
        axisBorder: {
          show: false
        },
        axisTicks: {
          show: false
        },
        labels: {
          show: true,
          style: {
            fontSize: '13px',
            colors: axisColor
          }
        }
      },
      yaxis: {
        labels: {
          show: false
        },
        forceNiceScale: true,
        tickAmount: 4
      }
    };
  if (typeof incomeChartEl !== 'undefined' && incomeChartEl !== null) {
    incomeChart = new ApexCharts(incomeChartEl, incomeChartConfig);
    incomeChart.render();
  }

  // Expenses Mini Chart - Radial Chart
  // --------------------------------------------------------------------
  const weeklyExpensesEl = document.querySelector('#expensesOfWeek'),
    weeklyExpensesConfig = {
      series: [0],
      chart: {
        width: 60,
        height: 60,
        type: 'radialBar'
      },
      plotOptions: {
        radialBar: {
          startAngle: 0,
          endAngle: 360,
          strokeWidth: '8',
          hollow: {
            margin: 2,
            size: '45%'
          },
          track: {
            strokeWidth: '50%',
            background: borderColor
          },
          dataLabels: {
            show: true,
            name: {
              show: false
            },
            value: {
              formatter: function (val) {
                return parseInt(val);
              },
              offsetY: 5,
              color: '#697a8d',
              fontSize: '13px',
              show: true
            }
          }
        }
      },
      fill: {
        type: 'solid',
        colors: config.colors.primary
      },
      stroke: {
        lineCap: 'round'
      },
      grid: {
        padding: {
          top: -10,
          bottom: -15,
          left: -10,
          right: -10
        }
      },
      states: {
        hover: {
          filter: {
            type: 'none'
          }
        },
        active: {
          filter: {
            type: 'none'
          }
        }
      }
    };
  if (typeof weeklyExpensesEl !== 'undefined' && weeklyExpensesEl !== null) {
    weeklyExpenses = new ApexCharts(weeklyExpensesEl, weeklyExpensesConfig);
    weeklyExpenses.render();
  }

  // Live Dashboard Stats
  // --------------------------------------------------------------------
  // Pulls every remaining widget's real numbers from dashboard_stats.php
  // (the Monthly Projects bar chart is already handled above via chart_data.php).
  const setText = function (id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value;
  };

  const escapeHtml = function (str) {
    const div = document.createElement('div');
    div.textContent = str == null ? '' : String(str);
    return div.innerHTML;
  };

  const loadDashboardStats = function () {
    fetch('dashboard_stats.php')
      .then(function (res) {
        return res.json();
      })
      .then(function (data) {
        const t = data.totals,
          trend = data.trend,
          w = data.workItems,
          c = data.clients;

        // Welcome card + top stat cards
        setText('welcomeInProgress', t.inProgress);
        setText('welcomeCompleted', t.completed);
        setText('statTotalClients', t.clients);
        setText('statTotalProjects', t.projects);
        setText('statOnHold', t.onHold);
        setText('statCompletedCard', t.completed);
        setText('statThisYear', t.thisYear);
        setText('statLastYear', t.lastYear);

        // Growth radial
        if (growthChart) {
          growthChart.updateSeries([t.completionRate]);
        }

        // Project trend sparkline
        if (profileReportChart) {
          profileReportChart.updateSeries([{ data: trend.data }]);
        }
        setText('trendMonthCount', trend.thisMonth);
        setText('trendPercent', Math.abs(trend.monthOverMonth));
        const trendIcon = document.getElementById('trendPercentIcon');
        const trendWrap = document.getElementById('trendPercentWrap');
        if (trendIcon && trendWrap) {
          const isUp = trend.monthOverMonth >= 0;
          trendIcon.className = isUp ? 'bx bx-chevron-up' : 'bx bx-chevron-down';
          trendWrap.className = 'text-nowrap fw-semibold ' + (isUp ? 'text-success' : 'text-danger');
        }

        // Work items donut
        if (statisticsChart) {
          statisticsChart.updateSeries([w.website, w.app, w.dashboard, w.technologies]);
        }
        setText('statTotalWorkItems', w.total);
        setText('workItemsSubtitle', w.total);
        setText('statWebsiteWork', w.website);
        setText('statAppWork', w.app);
        setText('statDashboardWork', w.dashboard);
        setText('statTechCount', w.technologies);

        // Client growth line chart + weekly radial
        if (incomeChart) {
          incomeChart.updateOptions({ xaxis: { categories: c.categories } });
          incomeChart.updateSeries([{ data: c.data }]);
        }
        if (weeklyExpenses) {
          weeklyExpenses.updateSeries([c.thisWeek]);
        }
        setText('statTotalClientsBalance', t.clients);
        const weekDeltaEl = document.getElementById('clientsWeekDeltaText');
        if (weekDeltaEl) {
          const d = c.weekDelta;
          weekDeltaEl.textContent = (d > 0 ? '+' + d : d) + ' vs last week';
        }

        // Recent projects list
        const listEl = document.getElementById('recentProjectsList');
        if (listEl) {
          if (!data.recentProjects.length) {
            listEl.innerHTML = '<li class="text-muted small">No projects yet.</li>';
          } else {
            const statusClass = {
              Completed: 'bg-label-success',
              'In Progress': 'bg-label-primary',
              'On Hold': 'bg-label-warning'
            };
            listEl.innerHTML = data.recentProjects
              .map(function (p) {
                const badge = statusClass[p.status] || 'bg-label-secondary';
                return (
                  '<li class="d-flex mb-4 pb-1">' +
                  '<div class="avatar flex-shrink-0 me-3">' +
                  '<span class="avatar-initial rounded bg-label-primary"><i class="bx bx-folder"></i></span>' +
                  '</div>' +
                  '<div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">' +
                  '<div class="me-2">' +
                  '<small class="text-muted d-block mb-1">' + escapeHtml(p.client_name) + '</small>' +
                  '<h6 class="mb-0">' + escapeHtml(p.project_name) + '</h6>' +
                  '</div>' +
                  '<div class="user-progress d-flex flex-column align-items-end gap-1">' +
                  '<span class="badge ' + badge + '">' + escapeHtml(p.status) + '</span>' +
                  '<small class="text-muted">' + escapeHtml(p.created_at) + '</small>' +
                  '</div>' +
                  '</div>' +
                  '</li>'
                );
              })
              .join('');
          }
        }
      })
      .catch(function (err) {
        console.error('Failed to load live dashboard stats:', err);
      });
  };

  loadDashboardStats();
  setInterval(loadDashboardStats, 30000);
})();
