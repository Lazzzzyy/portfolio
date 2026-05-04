window.DashCharts = (() => {
  const getTheme = () =>
    document.documentElement.getAttribute("data-theme") || "light";

  const PALETTE = {
    light: {
      status: ["#428368", "#8bbfa6", "#c5dfd4"],
      series: "#428368",
      foreground: "#0f1815",
      subtext: "rgba(15,24,21,0.45)",
      grid: "rgba(66,131,104,0.12)",
    },
    dark: {
      status: ["#87f414", "#4a7a1e", "#253d10"],
      series: "#87f414",
      foreground: "#d4e8be",
      subtext: "rgba(212,232,190,0.45)",
      grid: "rgba(191,216,165,0.1)",
    },
  };

  const buildBase = (theme) => {
    const p = PALETTE[theme];
    return {
      chart: {
        background: "transparent",
        fontFamily: '"Manrope", sans-serif',
        toolbar: { show: false },
        animations: { enabled: true, speed: 500 },
      },
      theme: { mode: theme },
      tooltip: {
        theme,
        style: { fontFamily: '"Manrope", sans-serif', fontSize: "13px" },
      },
      grid: { borderColor: p.grid, strokeDashArray: 4 },
      noData: {
        text: "No data yet",
        align: "center",
        verticalAlign: "middle",
        style: {
          color: p.subtext,
          fontSize: "14px",
          fontFamily: '"Manrope", sans-serif',
          fontWeight: "600",
        },
      },
    };
  };

  const instances = {};
  const initialized = new Set();
  let activeSection = null;

  const destroyAll = () => {
    Object.values(instances).forEach((ch) => ch?.destroy?.());
    Object.keys(instances).forEach((k) => delete instances[k]);
  };

  const readData = (id) => {
    const el = document.getElementById(id);
    if (!el) return null;
    try {
      return { el, data: JSON.parse(el.getAttribute("data-chart")) };
    } catch {
      return null;
    }
  };

  const axisLabel = (color) => ({
    style: { colors: color, fontFamily: '"Manrope", sans-serif', fontSize: "12px" },
  });

  const noAxis = { axisBorder: { show: false }, axisTicks: { show: false } };

  // ── Projects section ─────────────────────────────────────
  const initProjects = () => {
    const theme = getTheme();
    const p = PALETTE[theme];
    const b = buildBase(theme);

    const s = readData("chartStatus");
    if (s) {
      const allZero = s.data.values.every((v) => v === 0);
      instances.status = new ApexCharts(s.el, {
        ...b,
        chart: { ...b.chart, type: "donut", height: 280 },
        series: allZero ? [] : s.data.values,
        labels: allZero ? [] : s.data.labels,
        colors: p.status,
        plotOptions: {
          pie: {
            donut: {
              size: "60%",
              labels: {
                show: true,
                total: {
                  show: true,
                  label: "Total",
                  fontSize: "12px",
                  fontWeight: 600,
                  color: p.subtext,
                  formatter: (w) =>
                    w.globals.seriesTotals.reduce((a, c) => a + c, 0),
                },
              },
            },
          },
        },
        legend: {
          position: "bottom",
          fontFamily: '"Manrope", sans-serif',
          fontSize: "13px",
          labels: { colors: p.foreground },
        },
        dataLabels: { enabled: false },
      });
      instances.status.render();
    }

    const c = readData("chartCategory");
    if (c) {
      const catEmpty = c.data.values.length === 0;
      instances.category = new ApexCharts(c.el, {
        ...b,
        chart: { ...b.chart, type: "bar", height: 280 },
        series: catEmpty ? [] : [{ name: "Projects", data: c.data.values }],
        xaxis: { categories: catEmpty ? [] : c.data.labels, labels: axisLabel(p.subtext), ...noAxis },
        yaxis: { labels: { ...axisLabel(p.subtext), formatter: (v) => Math.round(v) }, min: 0 },
        colors: [p.series],
        plotOptions: { bar: { borderRadius: 4, columnWidth: "50%" } },
        dataLabels: { enabled: false },
        legend: { show: false },
      });
      instances.category.render();
    }

    const t = readData("chartTech");
    if (t) {
      const techEmpty = t.data.values.length === 0;
      const barH = techEmpty ? 240 : Math.max(240, t.data.labels.length * 38 + 60);
      instances.tech = new ApexCharts(t.el, {
        ...b,
        chart: { ...b.chart, type: "bar", height: barH },
        series: techEmpty ? [] : [{ name: "Projects", data: t.data.values }],
        xaxis: { categories: techEmpty ? [] : t.data.labels, labels: { ...axisLabel(p.subtext), formatter: (v) => Math.round(v) }, ...noAxis },
        yaxis: { labels: axisLabel(p.foreground) },
        colors: [p.series],
        plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: "52%" } },
        dataLabels: { enabled: false },
        legend: { show: false },
      });
      instances.tech.render();
    }

    const g = readData("chartGrowth");
    if (g) {
      const growthEmpty = g.data.values.length === 0;
      instances.growth = new ApexCharts(g.el, {
        ...b,
        chart: { ...b.chart, type: "line", height: 280 },
        series: growthEmpty ? [] : [{ name: "Projects", data: g.data.values }],
        xaxis: { categories: growthEmpty ? [] : g.data.labels, labels: axisLabel(p.subtext), ...noAxis },
        yaxis: { labels: { ...axisLabel(p.subtext), formatter: (v) => Math.round(v) }, min: 0 },
        colors: [p.series],
        stroke: { curve: "smooth", width: 3 },
        markers: { size: 5, colors: [p.series], strokeColors: p.series, strokeWidth: 0 },
        dataLabels: { enabled: false },
        legend: { show: false },
      });
      instances.growth.render();
    }
  };

  // ── Messages section ──────────────────────────────────────
  const initMessages = () => {
    const theme = getTheme();
    const p = PALETTE[theme];
    const b = buildBase(theme);

    const mc = readData("chartMsgCat");
    if (mc) {
      const allZero = mc.data.values.every((v) => v === 0);
      instances.msgCat = new ApexCharts(mc.el, {
        ...b,
        chart: { ...b.chart, type: "donut", height: 280 },
        series: allZero ? [] : mc.data.values,
        labels: allZero ? [] : mc.data.labels,
        colors: p.status,
        plotOptions: {
          pie: {
            donut: {
              size: "60%",
              labels: {
                show: true,
                total: {
                  show: true,
                  label: "Total",
                  fontSize: "12px",
                  fontWeight: 600,
                  color: p.subtext,
                  formatter: (w) =>
                    w.globals.seriesTotals.reduce((a, c) => a + c, 0),
                },
              },
            },
          },
        },
        legend: {
          position: "bottom",
          fontFamily: '"Manrope", sans-serif',
          fontSize: "13px",
          labels: { colors: p.foreground },
        },
        dataLabels: { enabled: false },
      });
      instances.msgCat.render();
    }

    const mt = readData("chartMsgTimeline");
    if (mt) {
      const timelineEmpty = mt.data.values.length === 0;
      instances.msgTimeline = new ApexCharts(mt.el, {
        ...b,
        chart: { ...b.chart, type: "line", height: 280 },
        series: timelineEmpty ? [] : [{ name: "Messages", data: mt.data.values }],
        xaxis: { categories: timelineEmpty ? [] : mt.data.labels, labels: axisLabel(p.subtext), ...noAxis },
        yaxis: { labels: { ...axisLabel(p.subtext), formatter: (v) => Math.round(v) }, min: 0 },
        colors: [p.series],
        stroke: { curve: "smooth", width: 3 },
        markers: { size: 5, colors: [p.series], strokeColors: p.series, strokeWidth: 0 },
        dataLabels: { enabled: false },
        legend: { show: false },
      });
      instances.msgTimeline.render();
    }
  };

  // ── Posts section ─────────────────────────────────────────
  const initPosts = () => {
    const theme = getTheme();
    const p = PALETTE[theme];
    const b = buildBase(theme);

    const pt = readData("chartPostsTime");
    if (pt) {
      const empty = pt.data.values.length === 0;
      instances.postsTime = new ApexCharts(pt.el, {
        ...b,
        chart: { ...b.chart, type: "line", height: 280 },
        series: empty ? [] : [{ name: "Posts", data: pt.data.values }],
        xaxis: { categories: empty ? [] : pt.data.labels, labels: axisLabel(p.subtext), ...noAxis },
        yaxis: { labels: { ...axisLabel(p.subtext), formatter: (v) => Math.round(v) }, min: 0 },
        colors: [p.series],
        stroke: { curve: "smooth", width: 3 },
        markers: { size: 5, colors: [p.series], strokeColors: p.series, strokeWidth: 0 },
        dataLabels: { enabled: false },
        legend: { show: false },
      });
      instances.postsTime.render();
    }

    const pc = readData("chartPostsCat");
    if (pc) {
      const allZero = pc.data.values.every((v) => v === 0);
      instances.postsCat = new ApexCharts(pc.el, {
        ...b,
        chart: { ...b.chart, type: "donut", height: 280 },
        series: allZero ? [] : pc.data.values,
        labels: allZero ? [] : pc.data.labels,
        colors: p.status,
        plotOptions: {
          pie: {
            donut: {
              size: "60%",
              labels: {
                show: true,
                total: {
                  show: true,
                  label: "Total",
                  fontSize: "12px",
                  fontWeight: 600,
                  color: p.subtext,
                  formatter: (w) =>
                    w.globals.seriesTotals.reduce((a, c) => a + c, 0),
                },
              },
            },
          },
        },
        legend: {
          position: "bottom",
          fontFamily: '"Manrope", sans-serif',
          fontSize: "13px",
          labels: { colors: p.foreground },
        },
        dataLabels: { enabled: false },
      });
      instances.postsCat.render();
    }

    const pv = readData("chartPostsViewed");
    if (pv) {
      const empty = pv.data.values.length === 0;
      const barH = empty ? 240 : Math.max(240, pv.data.labels.length * 42 + 60);
      instances.postsViewed = new ApexCharts(pv.el, {
        ...b,
        chart: { ...b.chart, type: "bar", height: barH },
        series: empty ? [] : [{ name: "Views", data: pv.data.values }],
        xaxis: { categories: empty ? [] : pv.data.labels, labels: { ...axisLabel(p.subtext), formatter: (v) => Math.round(v) }, ...noAxis },
        yaxis: { labels: axisLabel(p.foreground) },
        colors: [p.series],
        plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: "52%" } },
        dataLabels: { enabled: false },
        legend: { show: false },
      });
      instances.postsViewed.render();
    }
  };

  // ── Visitors section ───────────────────────────────────────
  const initVisitors = () => {
    const theme = getTheme();
    const p = PALETTE[theme];
    const b = buildBase(theme);

    const vt = readData("chartVisitorsTime");
    if (!vt) return;

    const allData = vt.data; // { day, week, month, year } each { labels[], values[] }

    const buildOpts = (grain) => {
      const { labels = [], values = [] } = allData[grain] ?? {};
      const empty = values.length === 0;
      return {
        ...b,
        chart: { ...b.chart, type: "area", height: 300 },
        series: empty ? [] : [{ name: "Visitors", data: values }],
        xaxis: { categories: empty ? [] : labels, labels: axisLabel(p.subtext), ...noAxis },
        yaxis: { labels: { ...axisLabel(p.subtext), formatter: (v) => Math.round(v) }, min: 0 },
        colors: [p.series],
        stroke: { curve: "smooth", width: 3 },
        markers: { size: 5, colors: [p.series], strokeColors: p.series, strokeWidth: 0 },
        fill: {
          type: "gradient",
          gradient: { shadeIntensity: 1, opacityFrom: 0.28, opacityTo: 0.02, stops: [0, 100] },
        },
        dataLabels: { enabled: false },
        legend: { show: false },
      };
    };

    instances.visitorsTime = new ApexCharts(vt.el, buildOpts("day"));
    instances.visitorsTime.render();

    const filter = document.getElementById("visitorsFilter");
    if (filter) {
      filter.addEventListener("change", () => {
        const { labels = [], values = [] } = allData[filter.value] ?? {};
        const empty = values.length === 0;
        instances.visitorsTime.updateOptions({
          series: empty ? [] : [{ name: "Visitors", data: values }],
          xaxis: { categories: empty ? [] : labels, labels: axisLabel(p.subtext), ...noAxis },
        }, false, true);
      });
    }
  };

  // ── Section dispatcher ───────────────────────────────────
  const initSection = (sectionId) => {
    if (initialized.has(sectionId)) return;
    initialized.add(sectionId);
    if (sectionId === "projects") initProjects();
    if (sectionId === "messages") initMessages();
    if (sectionId === "posts") initPosts();
    if (sectionId === "visitors") initVisitors();
  };

  const setActiveSection = (sectionId) => {
    activeSection = sectionId;
    initSection(sectionId);
  };

  // ── Theme change: destroy + re-init active section ───────
  new MutationObserver((mutations) => {
    for (const m of mutations) {
      if (m.attributeName === "data-theme") {
        destroyAll();
        initialized.clear();
        if (activeSection) initSection(activeSection);
        break;
      }
    }
  }).observe(document.documentElement, { attributes: true });

  return { setActiveSection };
})();
