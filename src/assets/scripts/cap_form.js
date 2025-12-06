/**
 * CAP Form JavaScript Controller
 * 
 * Handles multi-step form navigation without page reload
 * Requirements: 4.2, 4.3, 4.4
 */

class CAPFormController {
    constructor() {
        this.currentStep = 1;
        this.totalSteps = 4;
        this.issuesData = [];
        this.charts = {};
        
        // Step names for display
        this.stepNames = [
            'Check値の入力',
            'グラフ確認',
            'Action入力',
            'Plan入力'
        ];
        
        this.init();
    }
    
    /**
     * Initialize the form controller
     */
    init() {
        // Get DOM elements
        this.stepIndicator = document.getElementById('stepIndicator');
        this.btnPrev = document.getElementById('btnPrev');
        this.btnNext = document.getElementById('btnNext');
        this.btnSubmit = document.getElementById('btnSubmit');
        
        // Bind event listeners
        this.bindEvents();
        
        // Show initial step
        this.showStep(this.currentStep);
    }
    
    /**
     * Bind event listeners to buttons
     * Requirement 4.3, 4.4
     */
    bindEvents() {
        // Next button (Requirement 4.3)
        this.btnNext.addEventListener('click', () => {
            if (this.validateCurrentStep()) {
                this.nextStep();
            }
        });
        
        // Previous button (Requirement 4.4)
        this.btnPrev.addEventListener('click', () => {
            this.prevStep();
        });
        
        // Form submission validation
        const form = document.getElementById('capForm');
        form.addEventListener('submit', (e) => {
            if (!this.validateAllSteps()) {
                e.preventDefault();
                alert('全ての必須項目を入力してください。');
            }
        });
    }
    
    /**
     * Move to next step
     * Requirement 4.3
     */
    nextStep() {
        if (this.currentStep < this.totalSteps) {
            this.currentStep++;
            this.showStep(this.currentStep);
        }
    }
    
    /**
     * Move to previous step
     * Requirement 4.4
     */
    prevStep() {
        if (this.currentStep > 1) {
            this.currentStep--;
            this.showStep(this.currentStep);
        }
    }
    
    /**
     * Show specific step
     * Requirement 4.2, 4.3
     * @param {number} step - Step number to show
     */
    showStep(step) {
        // Hide all steps
        document.querySelectorAll('.step').forEach(s => {
            s.classList.remove('active');
        });
        
        // Show current step
        const currentStepElement = document.getElementById('step' + step);
        if (currentStepElement) {
            currentStepElement.classList.add('active');
        }
        
        // Update step indicator
        if (this.stepIndicator) {
            this.stepIndicator.textContent = 
                `ステップ ${step}/${this.totalSteps}: ${this.stepNames[step - 1]}`;
        }
        
        // Update button visibility
        this.updateButtonVisibility(step);
        
        // Special handling for step 2 (graph display)
        // Requirement 5.1-5.6
        if (step === 2) {
            this.renderCharts();
        }
        
        // Scroll to top of form
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    /**
     * Update button visibility based on current step
     * @param {number} step - Current step number
     */
    updateButtonVisibility(step) {
        // Previous button: show on all steps except first
        this.btnPrev.style.display = step > 1 ? 'block' : 'none';
        
        // Next button: show on all steps except last
        this.btnNext.style.display = step < this.totalSteps ? 'block' : 'none';
        
        // Submit button: show only on last step
        this.btnSubmit.style.display = step === this.totalSteps ? 'block' : 'none';
    }
    
    /**
     * Validate current step inputs
     * @returns {boolean} - True if validation passes
     */
    validateCurrentStep() {
        const currentStepElement = document.getElementById('step' + this.currentStep);
        if (!currentStepElement) return true;
        
        // Get all required inputs in current step
        const requiredInputs = currentStepElement.querySelectorAll('[required]');
        let isValid = true;
        let errorMessages = [];
        
        requiredInputs.forEach(input => {
            const value = input.value.trim();
            
            if (!value) {
                isValid = false;
                input.style.borderColor = '#f44336';
                
                // Get field label
                const label = document.querySelector(`label[for="${input.id}"]`);
                const fieldName = label ? label.textContent.replace('*', '').trim() : 'この項目';
                errorMessages.push(`${fieldName}を入力してください。`);
            } else {
                input.style.borderColor = '#ddd';
                
                // Additional validation for numeric inputs
                if (input.type === 'number') {
                    const numValue = parseFloat(value);
                    const min = input.min ? parseFloat(input.min) : null;
                    const max = input.max ? parseFloat(input.max) : null;
                    
                    if (min !== null && numValue < min) {
                        isValid = false;
                        input.style.borderColor = '#f44336';
                        errorMessages.push(`${input.id}は${min}以上の値を入力してください。`);
                    }
                    
                    if (max !== null && numValue > max) {
                        isValid = false;
                        input.style.borderColor = '#f44336';
                        errorMessages.push(`${input.id}は${max}以下の値を入力してください。`);
                    }
                }
            }
        });
        
        if (!isValid) {
            alert('入力エラー:\n' + errorMessages.join('\n'));
        }
        
        return isValid;
    }
    
    /**
     * Validate all steps before submission
     * @returns {boolean} - True if all validations pass
     */
    validateAllSteps() {
        let isValid = true;
        
        for (let step = 1; step <= this.totalSteps; step++) {
            const stepElement = document.getElementById('step' + step);
            if (!stepElement) continue;
            
            const requiredInputs = stepElement.querySelectorAll('[required]');
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                }
            });
        }
        
        return isValid;
    }
    
    /**
     * Set issues data for chart rendering
     * @param {Array} data - Issues data with history
     */
    setIssuesData(data) {
        this.issuesData = data;
    }
    
    /**
     * Render charts for all issues
     * Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6
     */
    renderCharts() {
        if (!this.issuesData || this.issuesData.length === 0) {
            console.warn('No issues data available for chart rendering');
            return;
        }
        
        this.issuesData.forEach(data => {
            const issue = data.issue;
            const recentCAPs = data.recent_caps || [];
            const issueId = issue.id;
            
            // Get new check value from step 1
            const valueInput = document.getElementById('value_' + issueId);
            if (!valueInput || !valueInput.value) {
                console.warn(`No value input found for issue ${issueId}`);
                return;
            }
            
            const newValue = parseFloat(valueInput.value);
            
            // Prepare chart data
            const labels = [];
            const values = [];
            
            // Add historical data (Requirement 5.1, 5.2)
            recentCAPs.forEach(cap => {
                const date = new Date(cap.created_at);
                labels.push(date.toLocaleDateString('ja-JP', { 
                    month: 'short', 
                    day: 'numeric' 
                }));
                values.push(parseFloat(cap.value));
            });
            
            // Add new value preview (Requirement 5.6)
            labels.push('今回');
            values.push(newValue);
            
            // Determine chart configuration based on metric type
            // Requirement 5.3, 5.4, 5.5
            const chartConfig = this.getChartConfig(issue, labels, values);
            
            // Get canvas element
            const canvas = document.getElementById('chart_' + issueId);
            if (!canvas) {
                console.warn(`Canvas not found for issue ${issueId}`);
                return;
            }
            
            const ctx = canvas.getContext('2d');
            
            // Destroy existing chart if it exists
            if (this.charts[issueId]) {
                this.charts[issueId].destroy();
            }
            
            // Create new chart
            this.charts[issueId] = new Chart(ctx, chartConfig);
        });
    }
    
    /**
     * Get chart configuration based on issue metric type
     * Requirements: 5.3, 5.4, 5.5
     * @param {Object} issue - Issue object
     * @param {Array} labels - Chart labels
     * @param {Array} values - Chart values
     * @returns {Object} - Chart.js configuration object
     */
    getChartConfig(issue, labels, values) {
        // Determine chart type (Requirement 5.4, 5.5)
        // Line chart for percentage and numeric
        // Line chart for scale_5 as well (can be customized)
        const chartType = 'line';
        
        // Base configuration
        const config = {
            type: chartType,
            data: {
                labels: labels,
                datasets: [{
                    label: issue.name,
                    data: values,
                    borderColor: '#4CAF50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    tension: 0.1,
                    fill: true,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#4CAF50',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    title: {
                        display: true,
                        text: issue.name + ' の推移',
                        font: {
                            size: 16,
                            weight: 'bold'
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed.y;
                                
                                // Add unit if available
                                if (issue.unit) {
                                    label += ' ' + issue.unit;
                                } else if (issue.metric_type === 'percentage') {
                                    label += '%';
                                }
                                
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: issue.metric_type === 'percentage' || 
                                     issue.metric_type === 'scale_5',
                        min: issue.metric_type === 'percentage' ? 0 : 
                             (issue.metric_type === 'scale_5' ? 1 : undefined),
                        max: issue.metric_type === 'percentage' ? 100 : 
                             (issue.metric_type === 'scale_5' ? 5 : undefined),
                        ticks: {
                            stepSize: issue.metric_type === 'scale_5' ? 1 : undefined
                        },
                        title: {
                            display: true,
                            text: this.getYAxisLabel(issue)
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: '日付'
                        }
                    }
                }
            }
        };
        
        return config;
    }
    
    /**
     * Get Y-axis label based on issue type
     * @param {Object} issue - Issue object
     * @returns {string} - Y-axis label
     */
    getYAxisLabel(issue) {
        if (issue.metric_type === 'percentage') {
            return 'パーセンテージ (%)';
        } else if (issue.metric_type === 'scale_5') {
            return '評価 (1-5)';
        } else if (issue.unit) {
            return '値 (' + issue.unit + ')';
        } else {
            return '値';
        }
    }
    
    /**
     * Destroy all charts (cleanup)
     */
    destroyCharts() {
        Object.keys(this.charts).forEach(key => {
            if (this.charts[key]) {
                this.charts[key].destroy();
            }
        });
        this.charts = {};
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Create form controller instance
    window.capFormController = new CAPFormController();
    
    // Set issues data if available
    if (typeof issuesData !== 'undefined') {
        window.capFormController.setIssuesData(issuesData);
    }
});
