'use strict';

(() => {
  const getTheme = () =>
    document.documentElement.getAttribute('data-theme') || 'light';

  const PALETTE = {
    light: {
      series:     '#428368',
      foreground: '#0f1815',
      subtext:    'rgba(15,24,21,0.45)',
      grid:       'rgba(66,131,104,0.12)',
    },
    dark: {
      series:     '#87f414',
      foreground: '#d4e8be',
      subtext:    'rgba(212,232,190,0.45)',
      grid:       'rgba(191,216,165,0.1)',
    },
  };

  const instances = {};

  const buildBase = (theme) => {
    const p = PALETTE[theme];
    return {
      chart: {
        background:  'transparent',
        fontFamily:  '"Manrope", sans-serif',
        toolbar:     { show: false },
        animations:  { enabled: true, speed: 500 },
      },
      theme:   { mode: theme },
      tooltip: {
        theme,
        style: { fontFamily: '"Manrope", sans-serif', fontSize: '13px' },
      },
      grid:   { borderColor: p.grid, strokeDashArray: 4 },
      noData: {
        text:            'No data yet',
        align:           'center',
        verticalAlign:   'middle',
        style: {
          color:      p.subtext,
          fontSize:   '14px',
          fontFamily: '"Manrope", sans-serif',
          fontWeight: '600',
        },
      },
    };
  };

  const axisLabel = (color) => ({
    style: { colors: color, fontFamily: '"Manrope", sans-serif', fontSize: '12px' },
  });

  const noAxis = { axisBorder: { show: false }, axisTicks: { show: false } };

  const readData = (id) => {
    const el = document.getElementById(id);
    if (!el) return null;
    try {
      return { el, data: JSON.parse(el.getAttribute('data-chart')) };
    } catch {
      return null;
    }
  };

  // ── 1. Visitor Activity (area, multi-grain) ─────────────────────────────

  const initVisitors = () => {
    const ref = readData('chartVisitors');
    if (!ref) return;

    const theme    = getTheme();
    const p        = PALETTE[theme];
    const b        = buildBase(theme);
    const allData  = ref.data;

    const buildOpts = (grain) => {
      const { labels = [], values = [] } = allData[grain] ?? {};
      const empty = values.length === 0;
      return {
        ...b,
        chart:  { ...b.chart, type: 'area', height: 300 },
        series: empty ? [] : [{ name: 'Visitors', data: values }],
        xaxis: {
          categories: empty ? [] : labels,
          labels:     axisLabel(p.subtext),
          ...noAxis,
        },
        yaxis: {
          labels: { ...axisLabel(p.subtext), formatter: (v) => Math.round(v) },
          min: 0,
        },
        colors: [p.series],
        stroke: { curve: 'smooth', width: 3 },
        markers: {
          size:         5,
          colors:       [p.series],
          strokeColors: p.series,
          strokeWidth:  0,
        },
        fill: {
          type:     'gradient',
          gradient: { shadeIntensity: 1, opacityFrom: 0.28, opacityTo: 0.02, stops: [0, 100] },
        },
        dataLabels: { enabled: false },
        legend:     { show: false },
      };
    };

    instances.visitors = new ApexCharts(ref.el, buildOpts('day'));
    instances.visitors.render();

    const filter = document.getElementById('visitorsFilter');
    if (filter) {
      filter.addEventListener('change', () => {
        const { labels = [], values = [] } = allData[filter.value] ?? {};
        const empty = values.length === 0;
        instances.visitors.updateOptions({
          series: empty ? [] : [{ name: 'Visitors', data: values }],
          xaxis:  { categories: empty ? [] : labels, labels: axisLabel(PALETTE[getTheme()].subtext), ...noAxis },
        }, false, true);
      });
    }
  };

  // ── 2. Most Viewed Projects (horizontal bar) ────────────────────────────

  const initTopProjects = () => {
    const ref = readData('chartTopProjects');
    if (!ref) return;

    const theme = getTheme();
    const p     = PALETTE[theme];
    const b     = buildBase(theme);
    const empty = ref.data.values.length === 0;
    const barH  = empty ? 240 : Math.max(240, ref.data.labels.length * 44 + 60);

    instances.topProjects = new ApexCharts(ref.el, {
      ...b,
      chart:  { ...b.chart, type: 'bar', height: barH },
      series: empty ? [] : [{ name: 'Views', data: ref.data.values }],
      xaxis: {
        categories: empty ? [] : ref.data.labels,
        labels:     axisLabel(p.subtext),
        ...noAxis,
      },
      yaxis: {
        labels: { ...axisLabel(p.foreground), formatter: (v) => Math.round(v) },
      },
      colors:      [p.series],
      plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '52%' } },
      dataLabels:  { enabled: false },
      legend:      { show: false },
    });
    instances.topProjects.render();
  };

  // ── 3. Top Blog Posts (horizontal bar) ─────────────────────────────────

  const initTopPosts = () => {
    const ref = readData('chartTopPosts');
    if (!ref) return;

    const theme = getTheme();
    const p     = PALETTE[theme];
    const b     = buildBase(theme);
    const empty = ref.data.values.length === 0;
    const barH  = empty ? 240 : Math.max(240, ref.data.labels.length * 44 + 60);

    instances.topPosts = new ApexCharts(ref.el, {
      ...b,
      chart:  { ...b.chart, type: 'bar', height: barH },
      series: empty ? [] : [{ name: 'Views', data: ref.data.values }],
      xaxis: {
        categories: empty ? [] : ref.data.labels,
        labels:     axisLabel(p.subtext),
        ...noAxis,
      },
      yaxis: {
        labels: { ...axisLabel(p.foreground), formatter: (v) => Math.round(v) },
      },
      colors:      [p.series],
      plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '52%' } },
      dataLabels:  { enabled: false },
      legend:      { show: false },
    });
    instances.topPosts.render();
  };

  // ── 4. Message Activity (line) ──────────────────────────────────────────

  const initMsgActivity = () => {
    const ref = readData('chartMsgActivity');
    if (!ref) return;

    const theme = getTheme();
    const p     = PALETTE[theme];
    const b     = buildBase(theme);
    const empty = ref.data.values.length === 0;

    instances.msgActivity = new ApexCharts(ref.el, {
      ...b,
      chart:  { ...b.chart, type: 'line', height: 280 },
      series: empty ? [] : [{ name: 'Messages', data: ref.data.values }],
      xaxis: {
        categories: empty ? [] : ref.data.labels,
        labels:     axisLabel(p.subtext),
        ...noAxis,
      },
      yaxis: {
        labels: { ...axisLabel(p.subtext), formatter: (v) => Math.round(v) },
        min: 0,
      },
      colors:     [p.series],
      stroke:     { curve: 'smooth', width: 3 },
      markers: {
        size:         5,
        colors:       [p.series],
        strokeColors: p.series,
        strokeWidth:  0,
      },
      dataLabels: { enabled: false },
      legend:     { show: false },
    });
    instances.msgActivity.render();
  };

  // ── Init all ────────────────────────────────────────────────────────────

  const initAll = () => {
    initVisitors();
    initTopProjects();
    initTopPosts();
    initMsgActivity();
  };

  const destroyAll = () => {
    Object.values(instances).forEach((ch) => ch?.destroy?.());
    Object.keys(instances).forEach((k) => delete instances[k]);
  };

  // ── Theme change: destroy + reinit ──────────────────────────────────────

  new MutationObserver((mutations) => {
    for (const m of mutations) {
      if (m.attributeName === 'data-theme') {
        destroyAll();
        initAll();
        break;
      }
    }
  }).observe(document.documentElement, { attributes: true });

  // ── AJAX stat refresh ────────────────────────────────────────────────────

  async function refreshStats(showSpinner) {
    const btn = document.getElementById('anRefreshBtn');
    if (showSpinner && btn) { btn.classList.add('dash-refresh-btn--spinning'); btn.disabled = true; }

    try {
      const res  = await fetch('./api/dashboard.php');
      const data = await res.json();
      if (!data.success) return;

      const s  = data.stats;
      const el = (id) => document.getElementById(id);
      if (el('anStatVisitors'))    el('anStatVisitors').textContent    = Number(s.visitors).toLocaleString();
      if (el('anStatPageViews'))   el('anStatPageViews').textContent   = Number(s.page_views).toLocaleString();
      if (el('anStatProjectViews')) el('anStatProjectViews').textContent = Number(s.project_views).toLocaleString();
      if (el('anStatMessages'))    el('anStatMessages').textContent    = Number(s.messages).toLocaleString();
    } catch (_) {
    } finally {
      if (btn) { btn.classList.remove('dash-refresh-btn--spinning'); btn.disabled = false; }
    }
  }

  const refreshBtn = document.getElementById('anRefreshBtn');
  if (refreshBtn) refreshBtn.addEventListener('click', () => refreshStats(true));

  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') refreshStats(false);
  });

  // ── Boot ────────────────────────────────────────────────────────────────

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
  } else {
    initAll();
  }
})();
