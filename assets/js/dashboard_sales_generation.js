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
  "Upcoming": "#dc3545", // Red
  "For Award": "#fbc02d", // Yellow
  "For Implementation": "#e6b422", // Darker Yellow
  Ongoing: "#d4a81e", // Even Darker Yellow
  Completed: "#198754", // Green
  "Collected": "#157347", // Darker Green
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
    Completed: "rgba(25, 135, 84, 0.15)",
    Accepted: "rgba(251, 192, 45, 0.15)",
    Pending: "rgba(220, 53, 69, 0.15)",
    Cancelled: "rgba(176, 42, 55, 0.15)",
    "Upcoming": "rgba(220, 53, 69, 0.15)",
    "For Award": "rgba(251, 192, 45, 0.15)",
    "For Implementation": "rgba(230, 180, 34, 0.15)",
    Ongoing: "rgba(212, 168, 30, 0.15)",
    "Collected": "rgba(21, 115, 71, 0.15)",
  },
  border: {
    Completed: "#198754",
    Accepted: "#fbc02d",
    Pending: "#dc3545",
    Cancelled: "#b02a37",
    "Upcoming": "#dc3545",
    "For Award": "#fbc02d",
    "For Implementation": "#e6b422",
    Ongoing: "#d4a81e",
    "Collected": "#157347",
  },
};

// Access data from global phpData object
const { projectStatusOverview, varianceData } = phpData;

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
  // 📊 Project Status Overview (Pie Chart)
  if (projectStatusOverview.length > 0) {
    const totalOverall = projectStatusOverview.reduce(
      (sum, r) => sum + parseFloat(r.total || 0),
      0
    );

    new Chart(document.getElementById("projectStatusChart"), {
      type: "pie",
      data: {
        labels: projectStatusOverview.map(
          (r) => `${r.status} (${((r.total / totalOverall) * 100).toFixed(1)}%)`
        ),
        datasets: [
          {
            data: projectStatusOverview.map((r) => r.total),
            backgroundColor: projectStatusOverview.map(
              (r) => projectStatusColors[r.status]
            ),
            borderColor: projectStatusOverview.map(
              (r) => projectStatusColors[r.status]
            ),
            borderWidth: 2,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "bottom",
            labels: {
              boxWidth: 14,
              font: { size: 12 },
            },
          },
        },
      },
    });
  } else {
    createEmptyChart(
      document.getElementById("projectStatusChart"),
      "No project data available"
    );
  }

  // 📊 BUDGET VARIANCE
  if (phpData.opportunity && phpData.opportunity.length > 0) {
    const projects = phpData.opportunity.map((p) => p.project_name);
    const contractData = phpData.opportunity.map(
      (p) => parseFloat(p.contract_amount) || 0
    );
    const abcData = phpData.opportunity.map((p) => parseFloat(p.ABC) || 0);

    new Chart(document.getElementById("opportunityChart"), {
      type: "bar",
      data: {
        labels: projects,
        datasets: [
          {
            label: "Contract Amount",
            data: contractData,
            backgroundColor: "#198754",
            borderColor: "#146c43",
            borderWidth: 1,
          },
          {
            label: "ABC",
            data: abcData,
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
              text: "Projects",
            },
          },
          y: {
            stacked: true,
            beginAtZero: true,
            title: {
              display: true,
              text: "Amount (₱)",
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
            displayColors: false, // This removes the color boxes entirely
            callbacks: {
              label: function (context) {
                const projectData = phpData.opportunity[context.dataIndex];
                const contractAmount =
                  parseFloat(projectData.contract_amount) || 0;
                const abc = parseFloat(projectData.ABC) || 0;
                const variance = abc - contractAmount;

                return [
                  `Budget Allocation: ₱${abc.toLocaleString()}`,
                  `Contract Amount: ₱${contractAmount.toLocaleString()}`,
                  `Variance: ₱${variance.toLocaleString()}`,
                ];
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
              // Only show percentage for Contract Amount (first dataset)
              if (i === 0) {
                const meta = chart.getDatasetMeta(i);
                meta.data.forEach((bar, index) => {
                  const data = dataset.data[index];
                  if (data > 0) {
                    const projectData = phpData.opportunity[index];
                    const contractAmount =
                      parseFloat(projectData.contract_amount) || 0;
                    const abc = parseFloat(projectData.ABC) || 0;
                    const total = contractAmount + abc;

                    if (total > 0) {
                      const percentage = Math.round(
                        (contractAmount / total) * 100
                      );

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
              }
            });
          },
        },
      ],
    });
  } else {
    createEmptyChart(
      document.getElementById("opportunityChart"),
      "No project financial data available"
    );
  }
});
