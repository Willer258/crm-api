import { startStimulusApp } from '@symfony/stimulus-bundle';
import Chart from 'chart.js/auto';

// Registers Stimulus controllers from controllers/admin/*_controller.js
const app = startStimulusApp();
app.debug = false;
window.Stimulus = app;

// Make Chart.js globally available
window.Chart = Chart;

// Register admin controllers
import dropdownController from './controllers/dropdown_controller.js';
import dashboardController from './controllers/admin/dashboard_controller.js';

app.register('dropdown', dropdownController);
app.register('admin--dashboard', dashboardController);

console.log('Admin app loaded');
