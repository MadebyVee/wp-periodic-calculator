function calculate() {
    console.log('Initial calculations complete.');
}

document.addEventListener('DOMContentLoaded', function () {
    calculate();

    var principal = rvCalcData.amount;
    var interestRate = rvCalcData.apr / 100 / 26;
    console.log('Principal:', principal);

    var daysInMonth = 30.4368;
    var daysInPeriod = 14;

    // Fetch the amortization period based on the model year
    var modelYear = rvCalcData.model_year;
    var amortizationPeriod = parseInt(getAmortizationPeriod(modelYear));

    var totalDaysLoan = amortizationPeriod * daysInMonth;
    var totalBiweeklyPeriodsLoan = Math.ceil(totalDaysLoan / daysInPeriod); // Round up to ensure accuracy

    var x = Math.pow(1 + interestRate, totalBiweeklyPeriodsLoan);
    var biweeklyPayment = principal * interestRate / (1 - Math.pow(1 + interestRate, -totalBiweeklyPeriodsLoan));

    if (isFinite(biweeklyPayment)) {
        var totalPayment = biweeklyPayment * totalBiweeklyPeriodsLoan;
        console.log('Total payment:', totalPayment);
        
        var totalBiweeklyPeriods60Months = Math.floor((60 * daysInMonth / daysInPeriod)); // Calculate biweekly periods for 60 months
        var totalInterest60Months = 0;
        var remainingPrincipal = principal;
        for (var i = 0; i < totalBiweeklyPeriods60Months; i++) {
            var interestThisPeriod = remainingPrincipal * interestRate;
            totalInterest60Months += interestThisPeriod;
            remainingPrincipal -= (biweeklyPayment - interestThisPeriod);
        }
        var costOfBorrowing = totalInterest60Months.toFixed(2);

        document.getElementById("payment").innerHTML = biweeklyPayment.toFixed(2);
        document.getElementById("cob").innerHTML = costOfBorrowing;
    }

    console.log(rvCalcData);
});

// Function to fetch the amortization period based on the model year
function getAmortizationPeriod(modelYear) {
    // Retrieve the amortization lengths for each year from the plugin settings
    var amortizationLengths = rvCalcData.amortizationLengths; // Change amortizationLengths to amortizationLengths
    console.log(rvCalcData.loan_period_months); // Check if loan period months are passed correctly

    // Check if amortization lengths are available
    if (amortizationLengths && amortizationLengths.hasOwnProperty(modelYear)) {
        // Return the amortization length for the specified model year
        return amortizationLengths[modelYear];
    } else {
        // If amortization length is not found for the model year, return a default value
        return 240; // Default amortization period in months
    }
}
