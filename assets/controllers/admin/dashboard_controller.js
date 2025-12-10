import { Controller } from '@hotwired/stimulus';

/*
 * Dashboard controller for admin dashboard
 * Handles Chart.js initialization and data visualization
 */
export default class extends Controller {
    static targets = ['mrrChart', 'tenantChart'];

    connect() {
        console.log('Dashboard controller connected');
        this.initializeCharts();
    }

    initializeCharts() {
        if (this.hasMrrChartTarget) {
            this.createMRRChart();
        }

        if (this.hasTenantChartTarget) {
            this.createTenantChart();
        }
    }

    createMRRChart() {
        const ctx = this.mrrChartTarget.getContext('2d');
        const chartData = this.getChartData(this.mrrChartTarget);

        // Sample data if no data provided
        const defaultData = {
            labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
            values: [1200, 1350, 1500, 1650, 1800, 2000, 2200, 2400, 2600, 2800, 3000, 3200]
        };

        const data = chartData || defaultData;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'MRR (€)',
                    data: data.values,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y.toLocaleString('fr-FR') + ' €';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('fr-FR') + ' €';
                            }
                        }
                    }
                }
            }
        });
    }

    createTenantChart() {
        const ctx = this.tenantChartTarget.getContext('2d');
        const chartData = this.getChartData(this.tenantChartTarget);

        // Sample data if no data provided
        const defaultData = {
            labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'],
            values: [10, 15, 22, 28, 35, 42, 50, 58, 67, 75, 84, 92]
        };

        const data = chartData || defaultData;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Tenants',
                    data: data.values,
                    backgroundColor: 'rgba(34, 197, 94, 0.8)',
                    borderColor: 'rgb(34, 197, 94)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 10
                        }
                    }
                }
            }
        });
    }

    getChartData(element) {
        const dataAttr = element.dataset.chartData;
        if (!dataAttr) return null;

        try {
            return JSON.parse(dataAttr);
        } catch (e) {
            console.error('Failed to parse chart data:', e);
            return null;
        }
    }
}
