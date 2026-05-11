const charts = {};

document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
});

async function initializeDashboard() {
    try {
        const response = await fetch('api/statistics_data.php', { cache: 'no-store' });
        if (!response.ok) {
            throw new Error(`Request failed with status ${response.status}`);
        }

        const data = await response.json();
        applyDashboardData(data);
        renderCharts(data);
    } catch (error) {
        console.error('Failed to load dashboard data:', error);
        renderCharts(getFallbackData());
    }
}

function applyDashboardData(data) {
    setText('totalRegisteredVendors', data.totalRegisteredVendors ?? 0);
    setText('businessCategoriesCount', data.businessCategoriesCount ?? 0);
    setText('healthComplianceRate', `${formatPercent(data.healthComplianceRate ?? 0)}`);

    setText('topCategoryName', data.topCategory?.name || 'N/A');
    setText('topCategoryCount', `${data.topCategory?.count ?? 0} vendors`);

    setText('averageVendorAge', `${Math.round(data.averageVendorAge ?? 0)} YEARS`);
    setText('ageRangeDetail', data.ageRangeDetail || 'No age data available');

    setText('monthlyGrowthRate', `${formatSignedPercent(data.monthlyGrowthRate ?? 0)}`);
    setText('monthlyGrowthDetail', data.monthlyGrowthDetail || 'Compared to the previous month');
}

function renderCharts(data) {
    destroyCharts();

    charts.growth = createLineChart('growthChart', data.growthTrend);
    charts.gender = createDoughnutChart('genderChart', data.genderDistribution);
    charts.category = createBarChart('categoryChart', data.categoryDistribution, '#89c989');
    charts.age = createBarChart('ageChart', data.ageDistribution, '#89c989');
    charts.compliance = createComplianceChart('complianceChart', data.complianceOverview);
}

function destroyCharts() {
    Object.values(charts).forEach(function(chart) {
        if (chart) {
            chart.destroy();
        }
    });
    Object.keys(charts).forEach(function(key) {
        charts[key] = null;
    });
}

function createLineChart(canvasId, growthTrend) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) {
        return null;
    }

    return new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: {
            labels: growthTrend.labels,
            datasets: [{
                label: 'New Registrations',
                data: growthTrend.values,
                borderColor: '#8fc98f',
                backgroundColor: 'rgba(143, 201, 143, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointBackgroundColor: '#8fc98f',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                filler: { propagate: true }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    suggestedMax: Math.max(10, ...(growthTrend.values || [])),
                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    ticks: {
                        font: { family: "'Jost', sans-serif", size: 12 }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { family: "'Jost', sans-serif", size: 12 }
                    }
                }
            }
        }
    });
}

function createDoughnutChart(canvasId, genderDistribution) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) {
        return null;
    }

    return new Chart(canvas.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: genderDistribution.labels,
            datasets: [{
                data: genderDistribution.values,
                backgroundColor: ['#a4b87e', '#caedd8'],
                borderColor: ['#fff', '#fff'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: { family: "'Jost', sans-serif", size: 12 },
                        padding: 20
                    }
                }
            }
        }
    });
}

function createBarChart(canvasId, chartData, color) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) {
        return null;
    }

    return new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: chartData.label || 'Count',
                data: chartData.values,
                backgroundColor: chartData.colors || color,
                borderColor: '#fff',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    ticks: {
                        font: { family: "'Jost', sans-serif", size: 11 }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { family: "'Jost', sans-serif", size: 10 }
                    }
                }
            }
        }
    });
}

function createComplianceChart(canvasId, complianceOverview) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) {
        return null;
    }

    return new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels: complianceOverview.labels,
            datasets: [
                {
                    label: 'Compliant',
                    data: complianceOverview.compliant,
                    backgroundColor: '#8ac98a',
                    borderColor: '#fff',
                    borderWidth: 1
                },
                {
                    label: 'Non-Compliant',
                    data: complianceOverview.nonCompliant,
                    backgroundColor: '#ff6b6b',
                    borderColor: '#fff',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: { family: "'Jost', sans-serif", size: 12 },
                        padding: 20
                    }
                }
            },
            scales: {
                x: {
                    stacked: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    ticks: {
                        font: { family: "'Jost', sans-serif", size: 11 }
                    }
                },
                y: {
                    stacked: true,
                    grid: { display: false },
                    ticks: {
                        font: { family: "'Jost', sans-serif", size: 11 }
                    }
                }
            }
        }
    });
}

function setText(elementId, value) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = value;
    }
}

function formatPercent(value) {
    return `${Number(value).toFixed(1)}%`;
}

function formatSignedPercent(value) {
    const numericValue = Number(value);
    const sign = numericValue > 0 ? '+' : '';
    return `${sign}${numericValue.toFixed(1)}%`;
}

function getFallbackData() {
    return {
        totalRegisteredVendors: 0,
        businessCategoriesCount: 0,
        healthComplianceRate: 0,
        topCategory: { name: 'N/A', count: 0 },
        averageVendorAge: 0,
        ageRangeDetail: 'No age data available',
        monthlyGrowthRate: 0,
        monthlyGrowthDetail: 'Compared to the previous month',
        growthTrend: { labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'], values: [0, 0, 0, 0, 0, 0, 0, 0] },
        genderDistribution: { labels: ['Male', 'Female'], values: [0, 0] },
        categoryDistribution: { labels: [], values: [], colors: [] },
        ageDistribution: { labels: ['18-25', '26-35', '36-45', '46-55', '56-65', '65+'], values: [0, 0, 0, 0, 0, 0] },
        complianceOverview: {
            labels: ['Hand Washing Stations', 'Color-Coded Items', 'Protective Gear', 'Stall Cleaning', 'No Smoking'],
            compliant: [0, 0, 0, 0, 0],
            nonCompliant: [0, 0, 0, 0, 0]
        }
    };
}
