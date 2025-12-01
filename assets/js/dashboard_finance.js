// Delivery Status Colors (Green-Yellow-Red)
const deliveryStatusColors = {
  Delivered: "#198754", // Green
  Accepted: "#fbc02d", // Yellow
  Pending: "#dc3545", // Red
  Cancelled: "#b02a37", // Darker Red
  Not: "#a52834", // Even Darker Red
};

// Project Status Colors (Green-Yellow-Red)
const projectStatusColors = {
  "Pending Evaluation": "#dc3545", // Red
  "For Award": "#fbc02d", // Yellow
  "For Implementation": "#e6b422", // Darker Yellow
  Ongoing: "#d4a81e", // Even Darker Yellow
  Delivered: "#198754", // Green
  Completed: "#157347", // Darker Green
};

const primaryColors = [
  "#198754", // Green
  "#157347", // Darker Green
  "#fbc02d", // Yellow
  "#e6b422", // Darker Yellow
  "#dc3545", // Red
  "#b02a37", // Darker Red
  "#22c55e", // Bright Green
  "#facc15", // Bright Yellow
];

// Color variants
const colorVariants = {
  light: {
    Delivered: "rgba(25, 135, 84, 0.15)",
    Accepted: "rgba(251, 192, 45, 0.15)",
    Pending: "rgba(220, 53, 69, 0.15)",
    Cancelled: "rgba(176, 42, 55, 0.15)",
    "Pending Evaluation": "rgba(220, 53, 69, 0.15)",
    "For Award": "rgba(251, 192, 45, 0.15)",
    "For Implementation": "rgba(230, 180, 34, 0.15)",
    Ongoing: "rgba(212, 168, 30, 0.15)",
    Completed: "rgba(21, 115, 71, 0.15)",
  },
  border: {
    Delivered: "#198754",
    Accepted: "#fbc02d",
    Pending: "#dc3545",
    Cancelled: "#b02a37",
    "Pending Evaluation": "#dc3545",
    "For Award": "#fbc02d",
    "For Implementation": "#e6b422",
    Ongoing: "#d4a81e",
    Completed: "#157347",
  },
};

// Access data from global phpData object
const {
  itemVariance,
  incomeByItem,
  expenseByItem,
  cashflowData,
  selectedProject,
} = phpData;

// Function to get color based on income amount (for income by item chart)
function getIncomeColor(income, maxIncome) {
  if (maxIncome === 0) return primaryColors[0];

  const percentage = (income / maxIncome) * 100;

  if (percentage <= 20) {
    return primaryColors[4]; // Lowest income - First color
  } else if (percentage <= 40) {
    return primaryColors[3]; // Low income - Second color
  } else if (percentage <= 60) {
    return primaryColors[2]; // Medium income - Third color
  } else if (percentage <= 80) {
    return primaryColors[1]; // High income - Fourth color
  } else {
    return primaryColors[0]; // Highest income - Fifth color
  }
}

// Function to get color based on expense amount (for expense by item chart)
function getExpenseColor(expense, maxExpense) {
  if (maxExpense === 0) return "#dc3545";

  const percentage = (expense / maxExpense) * 100;

  if (percentage <= 20) {
    return "#ff6b6b"; // Light red - Lowest expenses
  } else if (percentage <= 40) {
    return "#fa5252"; // Medium light red
  } else if (percentage <= 60) {
    return "#e03131"; // Medium red
  } else if (percentage <= 80) {
    return "#c92a2a"; // Dark red
  } else {
    return "#a61e4d"; // Darkest red - Highest expenses
  }
}

// Function to get color based on income-expense ratio (for combined chart)
function getIncomeExpenseRatioColor(income, expense) {
  if (income === 0 && expense === 0) return "#6c757d"; // Gray for no data

  if (income === 0) return "#dc3545"; // Red for no income
  if (expense === 0) return "#28a745"; // Green for no expenses

  const ratio = income / expense;

  if (ratio < 0.5) {
    return "#dc3545"; // Red - Expenses much higher than income
  } else if (ratio < 1) {
    return "#fd7e14"; // Orange - Expenses higher than income
  } else if (ratio < 1.5) {
    return "#ffc107"; // Yellow - Income slightly higher than expenses
  } else if (ratio < 2) {
    return "#20c997"; // Teal - Income moderately higher than expenses
  } else {
    return "#28a745"; // Green - Income much higher than expenses
  }
}

// Function to handle empty data and create a placeholder chart
function createEmptyChart(ctx, message) {
  return new Chart(ctx, {
    type: "bar",
    data: {
      labels: ["No Data"],
      datasets: [
        {
          label: message,
          data: [0],
          backgroundColor: "rgba(128, 128, 128, 0.3)",
          borderColor: "rgba(128, 128, 128, 0.8)",
          borderWidth: 1,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          max: 1,
        },
      },
    },
  });
}

document.addEventListener("DOMContentLoaded", function () {
  // Cashflow Chart - Actual Expense vs Income (Timeline)
  if (cashflowData && cashflowData.length > 0) {
    const months = cashflowData.map((r) => r.month);
    const incomeValues = cashflowData.map(
      (r) => parseFloat(r.total_income) || 0
    );
    const expenseValues = cashflowData.map(
      (r) => parseFloat(r.total_expense) || 0
    );
    const netCashflowValues = cashflowData.map(
      (r) => parseFloat(r.net_cashflow) || 0
    );

    new Chart(document.getElementById("cashflowChart"), {
      type: "line",
      data: {
        labels: months,
        datasets: [
          {
            label: "Income",
            data: incomeValues,
            borderColor: "#28a745",
            backgroundColor: "rgba(40, 167, 69, 0.1)",
            borderWidth: 3,
            tension: 0.3,
            fill: true,
            order: 2,
          },
          {
            label: "Expense",
            data: expenseValues,
            borderColor: "#dc3545",
            backgroundColor: "rgba(220, 53, 69, 0.1)",
            borderWidth: 3,
            tension: 0.3,
            fill: true,
            order: 2,
          },
          {
            label: "Profit",
            data: netCashflowValues,
            borderColor: "#ffc107",
            backgroundColor: "rgba(255, 193, 7, 0.15)",
            borderWidth: 4,
            tension: 0.3,
            fill: true,
            order: 1,
            borderDash: [5, 5],
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: "index",
          intersect: false,
        },
        scales: {
          x: {
            title: {
              display: true,
              text: "Timeline (Month)",
              font: {
                size: 14,
                weight: "bold",
              },
            },
            grid: {
              display: false,
            },
          },
          y: {
            beginAtZero: true,
            title: {
              display: true,
              text: "Amount (₱)",
              font: {
                size: 14,
                weight: "bold",
              },
            },
            ticks: {
              callback: function (value) {
                return "₱" + value.toLocaleString();
              },
            },
          },
        },
        plugins: {
          tooltip: {
            callbacks: {
              title: function (context) {
                return "Month: " + context[0].label;
              },
              label: function (context) {
                const label = context.dataset.label || "";
                const value = parseFloat(context.parsed.y);
                return (
                  label +
                  ": ₱" +
                  value.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                  })
                );
              },
              footer: function (context) {
                const monthData = cashflowData[context[0].dataIndex];
                const income = parseFloat(monthData.total_income);
                const expense = parseFloat(monthData.total_expense);
                const net = parseFloat(monthData.net_cashflow);
              },
            },
            backgroundColor: "rgba(0, 0, 0, 0.8)",
            padding: 12,
            titleFont: {
              size: 14,
              weight: "bold",
            },
            bodyFont: {
              size: 13,
            },
            footerFont: {
              size: 12,
              weight: "normal",
            },
          },
          legend: {
            display: true,
            position: "top",
            labels: {
              usePointStyle: true,
              padding: 15,
              font: {
                size: 12,
              },
            },
          },
        },
      },
    });
  } else {
    createEmptyChart(
      document.getElementById("cashflowChart"),
      "No cashflow data available"
    );
  }

  // 📊 ITEM PRICE VARIANCE CHART
  if (phpData.itemVariance && phpData.itemVariance.length > 0) {
    const items = phpData.itemVariance.map((p) => p.item_name);
    const ourPriceData = phpData.itemVariance.map(
      (p) => parseFloat(p.our_price) || 0
    );
    const factoryPriceData = phpData.itemVariance.map(
      (p) => parseFloat(p.factory_price) || 0
    );

    new Chart(document.getElementById("itemPriceVarianceChart"), {
      type: "bar",
      data: {
        labels: items,
        datasets: [
          {
            label: "Price",
            data: ourPriceData,
            backgroundColor: "#198754",
            borderColor: "#146c43",
            borderWidth: 1,
          },
          {
            label: "Factory Price",
            data: factoryPriceData,
            backgroundColor: "#fbc02d",
            borderColor: "#f9a825",
            borderWidth: 1,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            stacked: true,
            title: {
              display: true,
              text: "Items",
            },
          },
          y: {
            stacked: true,
            beginAtZero: true,
            title: {
              display: true,
              text: "Price (₱)",
            },
            ticks: {
              callback: function (value) {
                return "₱" + value.toLocaleString();
              },
            },
          },
        },
        plugins: {
          tooltip: {
            callbacks: {
              label: function (context) {
                const value = context.parsed.y;
                return `${context.dataset.label}: ₱${value.toLocaleString()}`;
              },
              afterLabel: function (context) {
                const itemData = phpData.itemVariance[context.dataIndex];
                const ourPrice = parseFloat(itemData.our_price) || 0;
                const factoryPrice = parseFloat(itemData.factory_price) || 0;
                const total = ourPrice + factoryPrice;

                if (total > 0) {
                  const percentage =
                    context.dataset.label === "Our Price"
                      ? Math.round((ourPrice / total) * 100)
                      : Math.round((factoryPrice / total) * 100);
                  return `(${percentage}%)`;
                }
                return "";
              },
            },
          },
        },
      },
      plugins: [
        {
          afterDatasetsDraw: function (chart) {
            const ctx = chart.ctx;
            chart.data.datasets.forEach((dataset, i) => {
              const meta = chart.getDatasetMeta(i);
              meta.data.forEach((bar, index) => {
                const data = dataset.data[index];
                if (data > 0) {
                  const itemData = phpData.itemVariance[index];
                  const ourPrice = parseFloat(itemData.our_price) || 0;
                  const factoryPrice = parseFloat(itemData.factory_price) || 0;
                  const total = ourPrice + factoryPrice;

                  if (total > 0) {
                    const percentage =
                      i === 0
                        ? Math.round((ourPrice / total) * 100)
                        : Math.round((factoryPrice / total) * 100);

                    ctx.fillStyle = "#fff";
                    ctx.font = "bold 12px Arial";
                    ctx.textAlign = "center";
                    ctx.textBaseline = "middle";
                    ctx.fillText(
                      percentage + "%",
                      bar.x,
                      bar.y + bar.height / 2
                    );
                  }
                }
              });
            });
          },
        },
      ],
    });
  } else {
    createEmptyChart(
      document.getElementById("itemPriceVarianceChart"),
      "No item price variance data available"
    );
  }

  // Income by Item Chart - Items (Horizontal Bar Chart)
  if (phpData.incomeByItem && phpData.incomeByItem.length > 0) {
    const itemNames = phpData.incomeByItem.map((item) => {
      const name = item.item_name;
      return name.length > 25 ? name.substring(0, 25) + "..." : name;
    });
    const itemIncomes = phpData.incomeByItem.map((item) =>
      parseFloat(item.total_income)
    );

    // Get max income for color calculation
    const maxIncome = Math.max(...itemIncomes);

    // Generate colors based on income amounts
    const incomeColors = itemIncomes.map((income) =>
      getIncomeColor(income, maxIncome)
    );

    new Chart(document.getElementById("incomeByItemChart"), {
      type: "bar",
      data: {
        labels: itemNames,
        datasets: [
          {
            label: "Income per Item",
            data: itemIncomes,
            backgroundColor: incomeColors,
            borderColor: incomeColors,
            borderWidth: 2,
          },
        ],
      },
      options: {
        indexAxis: "y",
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            beginAtZero: true,
            title: {
              display: true,
              text: "Amount (₱)",
            },
          },
        },
        plugins: {
          legend: {
            display: false,
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                const itemData = phpData.incomeByItem[context.dataIndex];
                return [
                  `Income: ₱${parseFloat(
                    itemData.total_income
                  ).toLocaleString()}`,
                  `Qty Sold: ${itemData.total_qty_sold}`,
                ];
              },
              title: function (context) {
                return phpData.incomeByItem[context[0].dataIndex].item_name;
              },
            },
          },
        },
      },
    });
  } else {
    createEmptyChart(
      document.getElementById("incomeByItemChart"),
      "No income by item data available"
    );
  }

  // Expense by Item Chart - Items (Horizontal Bar Chart)
  if (phpData.expenseByItem && phpData.expenseByItem.length > 0) {
    const itemNames = phpData.expenseByItem.map((item) => {
      const name = item.item_name;
      return name.length > 25 ? name.substring(0, 25) + "..." : name;
    });
    const itemExpenses = phpData.expenseByItem.map((item) =>
      parseFloat(item.total_expense)
    );

    // Get max expense for color calculation
    const maxExpense = Math.max(...itemExpenses);

    // Generate colors based on expense amounts
    const expenseColors = itemExpenses.map((expense) =>
      getExpenseColor(expense, maxExpense)
    );

    new Chart(document.getElementById("expenseByItemChart"), {
      type: "bar",
      data: {
        labels: itemNames,
        datasets: [
          {
            label: "Expense per Item",
            data: itemExpenses,
            backgroundColor: expenseColors,
            borderColor: expenseColors,
            borderWidth: 2,
          },
        ],
      },
      options: {
        indexAxis: "y",
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            beginAtZero: true,
            title: {
              display: true,
              text: "Amount (₱)",
            },
          },
        },
        plugins: {
          legend: {
            display: false,
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                const itemData = phpData.expenseByItem[context.dataIndex];
                return [
                  `Expense: ₱${parseFloat(
                    itemData.total_expense
                  ).toLocaleString()}`,
                  `Qty Purchased: ${itemData.total_qty_purchased}`,
                ];
              },
              title: function (context) {
                return phpData.expenseByItem[context[0].dataIndex].item_name;
              },
            },
          },
        },
      },
    });
  } else {
    createEmptyChart(
      document.getElementById("expenseByItemChart"),
      "No expense by item data available"
    );
  }

  // Income & Expense by Item Chart - Vertical (Income vs Expense with ratio-based colors)
  if (phpData.incomeExpenseByItem && phpData.incomeExpenseByItem.length > 0) {
    const itemNames = phpData.incomeExpenseByItem.map((item) => {
      const name = item.item_name;
      return name.length > 20 ? name.substring(0, 20) + "..." : name;
    });
    const itemIncomes = phpData.incomeExpenseByItem.map((item) =>
      parseFloat(item.total_income)
    );
    const itemExpenses = phpData.incomeExpenseByItem.map((item) =>
      parseFloat(item.total_expense)
    );

    // Generate colors based on income-expense ratio
    const barColors = phpData.incomeExpenseByItem.map((item) =>
      getIncomeExpenseRatioColor(
        parseFloat(item.total_income),
        parseFloat(item.total_expense)
      )
    );

    new Chart(document.getElementById("incomeExpenseByItemChart"), {
      type: "bar",
      data: {
        labels: itemNames,
        datasets: [
          {
            label: "Income",
            data: itemIncomes,
            backgroundColor: "#28a745",
            borderColor: "#198754",
            borderWidth: 1,
          },
          {
            label: "Expense",
            data: itemExpenses,
            backgroundColor: "#dc3545",
            borderColor: "#b02a37",
            borderWidth: 1,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            title: {
              display: true,
              text: "Items",
            },
          },
          y: {
            beginAtZero: true,
            title: {
              display: true,
              text: "Amount (₱)",
            },
          },
        },
        plugins: {
          legend: {
            display: true,
            position: "top",
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                const itemData = phpData.incomeExpenseByItem[context.dataIndex];
                if (context.datasetIndex === 0) {
                  // Income
                  return `Income: ₱${parseFloat(
                    itemData.total_income
                  ).toLocaleString()} (${itemData.total_qty_sold} sold)`;
                } else {
                  // Expense
                  return `Expense: ₱${parseFloat(
                    itemData.total_expense
                  ).toLocaleString()} (${
                    itemData.total_qty_purchased
                  } purchased)`;
                }
              },
            },
          },
        },
      },
    });
  } else {
    createEmptyChart(
      document.getElementById("incomeExpenseByItemChart"),
      "No income/expense data available"
    );
  }
});

setInterval(() => location.reload(), 30000);
