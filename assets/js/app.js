/**
 * ==============================================================================
 * SecOps TermVault - Frontend Reactive Application
 * Lead Architect: 0745
 * Organization: KoiTech
 * Operational Codename: 0745
 * Features: Instant search, parameter injector, command palette, clipboard copy
 * ==============================================================================
 */

document.addEventListener('DOMContentLoaded', () => {
  // State
  const state = {
    commands: [],
    categories: {},
    tags: {},
    stats: {},
    currentCategory: 'all',
    currentTag: null,
    favoriteOnly: false,
    searchQuery: '',
    sortBy: 'most_used',
    viewMode: 'grid', // 'grid' | 'list'
    paletteSelectedIndex: 0
  };

  // DOM Elements
  const el = {
    searchInput: document.getElementById('searchInput'),
    searchClearBtn: document.getElementById('searchClearBtn'),
    categoryTabs: document.getElementById('categoryTabs'),
    favFilterBtn: document.getElementById('favFilterBtn'),
    activeTagFilterWrapper: document.getElementById('activeTagFilterWrapper'),
    activeTagName: document.getElementById('activeTagName'),
    clearTagFilterBtn: document.getElementById('clearTagFilterBtn'),
    sortSelect: document.getElementById('sortSelect'),
    viewGridBtn: document.getElementById('viewGridBtn'),
    viewListBtn: document.getElementById('viewListBtn'),
    commandsGrid: document.getElementById('commandsGrid'),
    emptyState: document.getElementById('emptyState'),
    
    // Metrics
    metricTotal: document.getElementById('metricTotal'),
    metricFavorites: document.getElementById('metricFavorites'),
    metricCategories: document.getElementById('metricCategories'),
    metricCopies: document.getElementById('metricCopies'),

    // Modals
    commandModal: document.getElementById('commandModal'),
    commandForm: document.getElementById('commandForm'),
    modalTitle: document.getElementById('modalTitle'),
    cmdId: document.getElementById('cmdId'),
    cmdTitle: document.getElementById('cmdTitle'),
    cmdCommand: document.getElementById('cmdCommand'),
    cmdCategory: document.getElementById('cmdCategory'),
    cmdTags: document.getElementById('cmdTags'),
    cmdRisk: document.getElementById('cmdRisk'),
    cmdDesc: document.getElementById('cmdDesc'),
    cmdFavorite: document.getElementById('cmdFavorite'),
    closeCommandModalBtn: document.getElementById('closeCommandModalBtn'),
    cancelCommandModalBtn: document.getElementById('cancelCommandModalBtn'),
    openNewCommandBtn: document.getElementById('openNewCommandBtn'),

    // Param Injector Modal
    paramModal: document.getElementById('paramModal'),
    paramForm: document.getElementById('paramForm'),
    paramFieldsContainer: document.getElementById('paramFieldsContainer'),
    paramPreviewBox: document.getElementById('paramPreviewBox'),
    closeParamModalBtn: document.getElementById('closeParamModalBtn'),
    copyParamCommandBtn: document.getElementById('copyParamCommandBtn'),

    // Palette Modal
    paletteModal: document.getElementById('paletteModal'),
    paletteInput: document.getElementById('paletteInput'),
    paletteResults: document.getElementById('paletteResults'),
    openPaletteBtn: document.getElementById('openPaletteBtn'),

    // Import/Export Modal
    backupModal: document.getElementById('backupModal'),
    openBackupBtn: document.getElementById('openBackupBtn'),
    closeBackupModalBtn: document.getElementById('closeBackupModalBtn'),
    exportDownloadBtn: document.getElementById('exportDownloadBtn'),
    importFileInput: document.getElementById('importFileInput'),
    importPasteTextarea: document.getElementById('importPasteTextarea'),
    btnImportSubmit: document.getElementById('btnImportSubmit'),
    importModeSelect: document.getElementById('importModeSelect'),

    // Toasts
    toastContainer: document.getElementById('toastContainer')
  };

  // Subtle Web Audio Feedback (Zero files needed)
  function playBeep(freq = 600, duration = 0.05) {
    try {
      const AudioContext = window.AudioContext || window.webkitAudioContext;
      if (!AudioContext) return;
      const ctx = new AudioContext();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = 'sine';
      osc.frequency.setValueAtTime(freq, ctx.currentTime);
      gain.gain.setValueAtTime(0.04, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + duration);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start();
      osc.stop(ctx.currentTime + duration);
    } catch (e) {
      // Audio context may be restricted by browser policy before first gesture
    }
  }

  // Toast Notification
  function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    const iconSvg = type === 'success' 
      ? `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>`
      : `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;

    toast.innerHTML = `${iconSvg} <span>${escapeHtml(message)}</span>`;
    el.toastContainer.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(10px)';
      toast.style.transition = 'all 0.25s ease';
      setTimeout(() => toast.remove(), 260);
    }, 3200);
  }

  // Utilities
  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // Highlight parameter placeholders like <target_ip>, {{port}}, etc.
  function formatCommandPreview(commandText) {
    const escaped = escapeHtml(commandText);
    return escaped.replace(/(&lt;[^&gt;]+&gt;|\{\{[^}]+\}\})/g, '<span class="param-highlight">$1</span>');
  }

  // Extract parameter placeholder names from command
  function extractParameters(commandText) {
    const matches = commandText.match(/<([^>]+)>|\{\{([^}]+)\}\}/g);
    if (!matches) return [];
    // Clean brackets
    return [...new Set(matches.map(m => m.replace(/[<{}>]/g, '').trim()))];
  }

  // Fetch Commands with Current Filters
  async function fetchCommands() {
    const params = new URLSearchParams();
    params.set('action', 'list');
    if (state.searchQuery) params.set('q', state.searchQuery);
    if (state.currentCategory && state.currentCategory !== 'all') params.set('category', state.currentCategory);
    if (state.currentTag) params.set('tag', state.currentTag);
    if (state.favoriteOnly) params.set('favorite', '1');
    if (state.sortBy) params.set('sort', state.sortBy);

    try {
      const res = await fetch(`api.php?${params.toString()}`);
      const data = await res.json();
      if (data.success) {
        state.commands = data.data;
        renderCommands();
      } else {
        showToast(data.error || 'Failed to fetch commands', 'error');
      }
    } catch (err) {
      console.error(err);
      showToast('Network error while loading commands', 'error');
    }
  }

  // Fetch Categories & Stats
  async function fetchMetadata() {
    try {
      const [resCat, resStats] = await Promise.all([
        fetch('api.php?action=categories'),
        fetch('api.php?action=stats')
      ]);

      const dataCat = await resCat.json();
      const dataStats = await resStats.json();

      if (dataCat.success) {
        state.categories = dataCat.data;
        renderCategoryTabs();
        populateCategorySelect();
      }

      if (dataStats.success) {
        state.stats = dataStats.data;
        renderMetrics();
      }
    } catch (err) {
      console.error('Metadata load error:', err);
    }
  }

  // Render Metrics
  function renderMetrics() {
    if (!state.stats) return;
    el.metricTotal.textContent = state.stats.total_commands ?? 0;
    el.metricFavorites.textContent = state.stats.total_favorites ?? 0;
    el.metricCategories.textContent = state.stats.total_categories ?? 0;
    el.metricCopies.textContent = state.stats.total_copies ?? 0;
  }

  // Render Category Tabs
  function renderCategoryTabs() {
    el.categoryTabs.innerHTML = '';

    // "All" tab
    const allTab = document.createElement('button');
    allTab.className = `cat-tab ${state.currentCategory === 'all' ? 'active' : ''}`;
    allTab.innerHTML = `All <span class="tab-count">${state.stats.total_commands || 0}</span>`;
    allTab.onclick = () => {
      state.currentCategory = 'all';
      renderCategoryTabs();
      fetchCommands();
    };
    el.categoryTabs.appendChild(allTab);

    // Dynamic categories
    Object.entries(state.categories).forEach(([catName, count]) => {
      const tab = document.createElement('button');
      tab.className = `cat-tab ${state.currentCategory.toLowerCase() === catName.toLowerCase() ? 'active' : ''}`;
      tab.innerHTML = `${escapeHtml(catName)} <span class="tab-count">${count}</span>`;
      tab.onclick = () => {
        state.currentCategory = catName;
        renderCategoryTabs();
        fetchCommands();
      };
      el.categoryTabs.appendChild(tab);
    });
  }

  // Populate Categories in Add/Edit select
  function populateCategorySelect() {
    const cats = Object.keys(state.categories);
    const defaults = [
      'CLI & Workflows',
      'Recon & Scanning',
      'Exploitation & Shells',
      'Incident Response',
      'Network Defense',
      'Linux Admin & Hardening',
      'Web App Security',
      'Terminology & Concepts'
    ];

    const allOptions = [...new Set([...defaults, ...cats])];
    el.cmdCategory.innerHTML = '';
    allOptions.forEach(c => {
      const opt = document.createElement('option');
      opt.value = c;
      opt.textContent = c;
      el.cmdCategory.appendChild(opt);
    });
  }

  // Render Main Commands List / Grid
  function renderCommands() {
    el.commandsGrid.innerHTML = '';

    if (state.commands.length === 0) {
      el.emptyState.style.display = 'block';
      el.commandsGrid.style.display = 'none';
      return;
    }

    el.emptyState.style.display = 'none';
    el.commandsGrid.style.display = state.viewMode === 'grid' ? 'grid' : 'flex';
    el.commandsGrid.className = `commands-grid ${state.viewMode === 'list' ? 'list-mode' : ''}`;

    state.commands.forEach(cmd => {
      const card = createCommandCard(cmd);
      el.commandsGrid.appendChild(card);
    });
  }

  // Create Individual Card DOM
  function createCommandCard(cmd) {
    const card = document.createElement('div');
    card.className = `command-card ${cmd.is_favorite ? 'starred' : ''}`;
    card.dataset.id = cmd.id;

    const riskBadgeClass = `badge-risk-${cmd.risk_level || 'safe'}`;
    const riskLabel = (cmd.risk_level || 'safe').toUpperCase();

    const params = extractParameters(cmd.command);
    const hasParams = params.length > 0;

    // Build Tags HTML
    const tagsHtml = (cmd.tags || []).map(t => {
      return `<span class="tag-pill" data-tag="${escapeHtml(t)}">#${escapeHtml(t)}</span>`;
    }).join('');

    card.innerHTML = `
      <div>
        <div class="card-header">
          <div class="card-title-group">
            <h3 class="card-title">${escapeHtml(cmd.title)}</h3>
            <div class="card-meta-badges">
              <span class="badge badge-cat">${escapeHtml(cmd.category || 'General')}</span>
              <span class="badge ${riskBadgeClass}">${riskLabel}</span>
            </div>
          </div>
          <button class="btn-star ${cmd.is_favorite ? 'active' : ''}" title="${cmd.is_favorite ? 'Remove from favorites' : 'Pin to favorites'}" data-action="favorite">
            ${cmd.is_favorite ? '★' : '☆'}
          </button>
        </div>

        <div class="command-box">
          <code class="command-code">${formatCommandPreview(cmd.command)}</code>
          <div class="command-box-actions">
            ${hasParams ? `
              <button class="btn-fill-params" title="Fill dynamic variables (<ip>, <port>, etc.)" data-action="fill-params">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                Fill
              </button>
            ` : ''}
            <button class="btn-copy" data-action="copy" title="Copy to clipboard">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              <span>Copy</span>
            </button>
          </div>
        </div>

        ${cmd.description ? `<p class="card-description">${escapeHtml(cmd.description)}</p>` : ''}
      </div>

      <div class="card-footer">
        <div class="card-tags">
          ${tagsHtml}
        </div>
        <div class="card-actions-menu">
          <span class="copy-counter" title="Times copied">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            <span class="count-num">${cmd.copy_count || 0}</span>
          </span>
          <button class="btn-card-action" data-action="edit" title="Edit command">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </button>
          <button class="btn-card-action" data-action="clone" title="Duplicate command">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
          </button>
          <button class="btn-card-action btn-del" data-action="delete" title="Delete command">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
          </button>
        </div>
      </div>
    `;

    // Bind card event listeners
    bindCardEvents(card, cmd);

    return card;
  }

  // Bind Card Click Events
  function bindCardEvents(card, cmd) {
    // Copy button
    const copyBtn = card.querySelector('[data-action="copy"]');
    if (copyBtn) {
      copyBtn.onclick = (e) => {
        e.stopPropagation();
        executeCopy(cmd.command, cmd.id, copyBtn);
      };
    }

    // Fill parameters button
    const fillBtn = card.querySelector('[data-action="fill-params"]');
    if (fillBtn) {
      fillBtn.onclick = (e) => {
        e.stopPropagation();
        openParamInjectorModal(cmd);
      };
    }

    // Star / Favorite
    const starBtn = card.querySelector('[data-action="favorite"]');
    if (starBtn) {
      starBtn.onclick = async (e) => {
        e.stopPropagation();
        try {
          const res = await fetch('api.php?action=toggle_favorite', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: cmd.id })
          });
          const data = await res.json();
          if (data.success) {
            cmd.is_favorite = data.is_favorite;
            starBtn.innerHTML = cmd.is_favorite ? '★' : '☆';
            starBtn.classList.toggle('active', cmd.is_favorite);
            card.classList.toggle('starred', cmd.is_favorite);
            fetchMetadata(); // update stats
            playBeep(880, 0.04);
          }
        } catch (err) {
          console.error(err);
        }
      };
    }

    // Edit button
    const editBtn = card.querySelector('[data-action="edit"]');
    if (editBtn) {
      editBtn.onclick = (e) => {
        e.stopPropagation();
        openEditCommandModal(cmd);
      };
    }

    // Duplicate button
    const cloneBtn = card.querySelector('[data-action="clone"]');
    if (cloneBtn) {
      cloneBtn.onclick = (e) => {
        e.stopPropagation();
        openCloneCommandModal(cmd);
      };
    }

    // Delete button
    const delBtn = card.querySelector('[data-action="delete"]');
    if (delBtn) {
      delBtn.onclick = async (e) => {
        e.stopPropagation();
        if (confirm(`Are you sure you want to delete "${cmd.title}"?`)) {
          try {
            const res = await fetch('api.php?action=delete', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ id: cmd.id })
            });
            const data = await res.json();
            if (data.success) {
              showToast('Command deleted.', 'success');
              fetchCommands();
              fetchMetadata();
            } else {
              showToast(data.error || 'Failed to delete', 'error');
            }
          } catch (err) {
            console.error(err);
          }
        }
      };
    }

    // Tag clicks
    card.querySelectorAll('.tag-pill').forEach(tp => {
      tp.onclick = (e) => {
        e.stopPropagation();
        const tag = tp.dataset.tag;
        setTagFilter(tag);
      };
    });
  }

  // Copy to Clipboard Action
  async function executeCopy(text, cmdId, buttonElement) {
    try {
      if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(text);
      } else {
        // Fallback for non-https local contexts
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.focus();
        ta.select();
        document.execCommand('copy');
        ta.remove();
      }

      playBeep(750, 0.06);

      // Button Visual Feedback
      if (buttonElement) {
        const originalHtml = buttonElement.innerHTML;
        buttonElement.classList.add('copied');
        buttonElement.innerHTML = `
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          <span>COPIED!</span>
        `;
        setTimeout(() => {
          buttonElement.classList.remove('copied');
          buttonElement.innerHTML = originalHtml;
        }, 1800);
      }

      showToast(`Copied to clipboard: "${text.length > 35 ? text.substring(0, 32) + '...' : text}"`, 'success');

      // Silently increment copy counter
      if (cmdId) {
        fetch('api.php?action=increment_copy', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id: cmdId })
        }).then(r => r.json()).then(res => {
          if (res.success && res.copy_count) {
            const counterSpan = document.querySelector(`.command-card[data-id="${cmdId}"] .count-num`);
            if (counterSpan) counterSpan.textContent = res.copy_count;
            if (state.stats) {
              state.stats.total_copies = (state.stats.total_copies || 0) + 1;
              el.metricCopies.textContent = state.stats.total_copies;
            }
          }
        }).catch(() => {});
      }
    } catch (err) {
      console.error('Failed to copy:', err);
      showToast('Could not copy command to clipboard', 'error');
    }
  }

  // Tag Filtering
  function setTagFilter(tag) {
    state.currentTag = tag;
    el.activeTagName.textContent = `#${tag}`;
    el.activeTagFilterWrapper.style.display = 'inline-flex';
    fetchCommands();
  }

  function clearTagFilter() {
    state.currentTag = null;
    el.activeTagFilterWrapper.style.display = 'none';
    fetchCommands();
  }

  // Open Modal: Add New
  function openNewCommandModal() {
    el.commandForm.reset();
    el.modalTitle.textContent = 'Add Terminal Code / Terminology';
    el.cmdId.value = '';
    el.cmdRisk.value = 'safe';
    el.cmdFavorite.checked = false;
    el.commandModal.classList.add('active');
    el.cmdTitle.focus();
  }

  // Open Modal: Edit
  function openEditCommandModal(cmd) {
    el.commandForm.reset();
    el.modalTitle.textContent = 'Edit Command';
    el.cmdId.value = cmd.id;
    el.cmdTitle.value = cmd.title || '';
    el.cmdCommand.value = cmd.command || '';
    el.cmdCategory.value = cmd.category || 'CLI & Workflows';
    el.cmdTags.value = (cmd.tags || []).join(', ');
    el.cmdRisk.value = cmd.risk_level || 'safe';
    el.cmdDesc.value = cmd.description || '';
    el.cmdFavorite.checked = !!cmd.is_favorite;
    el.commandModal.classList.add('active');
    el.cmdTitle.focus();
  }

  // Open Modal: Clone/Duplicate
  function openCloneCommandModal(cmd) {
    openEditCommandModal(cmd);
    el.modalTitle.textContent = `Clone: ${cmd.title}`;
    el.cmdId.value = ''; // clears ID to create new entry
    el.cmdTitle.value = `${cmd.title} (Copy)`;
  }

  function closeCommandModal() {
    el.commandModal.classList.remove('active');
  }

  // Save Command Form Submission
  async function handleCommandFormSubmit(e) {
    e.preventDefault();
    const isEdit = Boolean(el.cmdId.value);
    const action = isEdit ? 'update' : 'create';

    const payload = {
      title: el.cmdTitle.value.trim(),
      command: el.cmdCommand.value.trim(),
      category: el.cmdCategory.value.trim(),
      tags: el.cmdTags.value.split(',').map(t => t.trim()).filter(Boolean),
      risk_level: el.cmdRisk.value,
      description: el.cmdDesc.value.trim(),
      is_favorite: el.cmdFavorite.checked
    };

    if (isEdit) {
      payload.id = el.cmdId.value;
    }

    if (!payload.title || !payload.command) {
      showToast('Title and Command are required.', 'error');
      return;
    }

    try {
      const res = await fetch(`api.php?action=${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.success) {
        showToast(data.message || 'Saved successfully.', 'success');
        closeCommandModal();
        fetchCommands();
        fetchMetadata();
      } else {
        showToast(data.error || 'Failed to save command.', 'error');
      }
    } catch (err) {
      console.error(err);
      showToast('Network error while saving.', 'error');
    }
  }

  // Parameter Injector Modal Logic
  let activeParamCommandTemplate = '';
  let activeParamCommandId = '';

  function openParamInjectorModal(cmd) {
    activeParamCommandTemplate = cmd.command;
    activeParamCommandId = cmd.id;
    const params = extractParameters(cmd.command);

    el.paramFieldsContainer.innerHTML = '';
    const inputMap = {};

    params.forEach(paramName => {
      const row = document.createElement('div');
      row.className = 'param-input-group';
      row.innerHTML = `
        <span class="param-name-badge">&lt;${escapeHtml(paramName)}&gt;</span>
        <input type="text" class="form-input code-font" placeholder="Value for ${escapeHtml(paramName)}..." data-param="${escapeHtml(paramName)}">
      `;
      el.paramFieldsContainer.appendChild(row);

      const input = row.querySelector('input');
      inputMap[paramName] = input;
      input.addEventListener('input', updateParamPreview);
    });

    function updateParamPreview() {
      let finalCommand = activeParamCommandTemplate;
      params.forEach(p => {
        const val = inputMap[p]?.value.trim();
        const placeholder = `<${p}>`;
        const altPlaceholder = `{{${p}}}`;
        if (val) {
          finalCommand = finalCommand.replaceAll(placeholder, val).replaceAll(altPlaceholder, val);
        }
      });
      el.paramPreviewBox.textContent = finalCommand;
    }

    updateParamPreview();
    el.paramModal.classList.add('active');

    // Focus first param input
    const firstInput = el.paramFieldsContainer.querySelector('input');
    if (firstInput) firstInput.focus();
  }

  function closeParamModal() {
    el.paramModal.classList.remove('active');
  }

  // Command Palette (Ctrl + K) Logic
  let paletteFiltered = [];

  function openPalette() {
    el.paletteModal.classList.add('active');
    el.paletteInput.value = '';
    state.paletteSelectedIndex = 0;
    renderPaletteItems(state.commands);
    setTimeout(() => el.paletteInput.focus(), 50);
  }

  function closePalette() {
    el.paletteModal.classList.remove('active');
  }

  function renderPaletteItems(list) {
    paletteFiltered = list.slice(0, 12);
    el.paletteResults.innerHTML = '';

    if (paletteFiltered.length === 0) {
      el.paletteResults.innerHTML = `<div style="padding: 1rem; color: var(--text-muted); text-align: center; font-size: 0.85rem;">No commands found.</div>`;
      return;
    }

    paletteFiltered.forEach((cmd, idx) => {
      const item = document.createElement('div');
      item.className = `palette-item ${idx === state.paletteSelectedIndex ? 'selected' : ''}`;
      item.innerHTML = `
        <div class="palette-item-left">
          <div class="palette-item-title">${escapeHtml(cmd.title)}</div>
          <div class="palette-item-code">${escapeHtml(cmd.command)}</div>
        </div>
        <div class="palette-item-cat">${escapeHtml(cmd.category || '')}</div>
      `;
      item.onclick = () => {
        executeCopy(cmd.command, cmd.id, null);
        closePalette();
      };
      el.paletteResults.appendChild(item);
    });
  }

  // Backup & Import Modal Logic
  function openBackupModal() {
    el.importPasteTextarea.value = '';
    el.importFileInput.value = '';
    el.backupModal.classList.add('active');
  }

  function closeBackupModal() {
    el.backupModal.classList.remove('active');
  }

  async function handleImportSubmit() {
    let jsonText = el.importPasteTextarea.value.trim();
    const mode = el.importModeSelect.value;

    if (!jsonText && el.importFileInput.files.length > 0) {
      const file = el.importFileInput.files[0];
      jsonText = await file.text();
    }

    if (!jsonText) {
      showToast('Please select a JSON file or paste JSON code.', 'error');
      return;
    }

    try {
      const res = await fetch(`api.php?action=import&mode=${mode}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ json_data: jsonText })
      });
      const data = await res.json();
      if (data.success) {
        showToast(data.message || 'Imported successfully!', 'success');
        closeBackupModal();
        fetchCommands();
        fetchMetadata();
      } else {
        showToast(data.error || 'Import failed.', 'error');
      }
    } catch (err) {
      console.error(err);
      showToast('Error importing JSON data.', 'error');
    }
  }

  // Search input debouncer
  let searchTimeout = null;
  el.searchInput.addEventListener('input', () => {
    const val = el.searchInput.value.trim();
    el.searchClearBtn.style.display = val ? 'block' : 'none';
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      state.searchQuery = val;
      fetchCommands();
    }, 180);
  });

  el.searchClearBtn.addEventListener('click', () => {
    el.searchInput.value = '';
    el.searchClearBtn.style.display = 'none';
    state.searchQuery = '';
    fetchCommands();
    el.searchInput.focus();
  });

  // Favorite toggle filter
  el.favFilterBtn.addEventListener('click', () => {
    state.favoriteOnly = !state.favoriteOnly;
    el.favFilterBtn.classList.toggle('active', state.favoriteOnly);
    fetchCommands();
  });

  // Clear tag filter
  el.clearTagFilterBtn.addEventListener('click', clearTagFilter);

  // Sort selector
  el.sortSelect.addEventListener('change', (e) => {
    state.sortBy = e.target.value;
    fetchCommands();
  });

  // View modes (Grid / List)
  el.viewGridBtn.addEventListener('click', () => {
    state.viewMode = 'grid';
    el.viewGridBtn.classList.add('active');
    el.viewListBtn.classList.remove('active');
    renderCommands();
  });

  el.viewListBtn.addEventListener('click', () => {
    state.viewMode = 'list';
    el.viewListBtn.classList.add('active');
    el.viewGridBtn.classList.remove('active');
    renderCommands();
  });

  // Modal Triggers
  el.openNewCommandBtn.addEventListener('click', openNewCommandModal);
  el.closeCommandModalBtn.addEventListener('click', closeCommandModal);
  el.cancelCommandModalBtn.addEventListener('click', closeCommandModal);
  el.commandForm.addEventListener('submit', handleCommandFormSubmit);

  el.closeParamModalBtn.addEventListener('click', closeParamModal);
  el.copyParamCommandBtn.addEventListener('click', () => {
    const readyCode = el.paramPreviewBox.textContent;
    executeCopy(readyCode, activeParamCommandId, el.copyParamCommandBtn);
    setTimeout(closeParamModal, 400);
  });

  // Command Palette Triggers
  el.openPaletteBtn.addEventListener('click', openPalette);
  el.paletteInput.addEventListener('input', () => {
    const query = el.paletteInput.value.toLowerCase().trim();
    if (!query) {
      renderPaletteItems(state.commands);
      return;
    }
    const filtered = state.commands.filter(c => {
      const pool = `${c.title} ${c.command} ${(c.tags || []).join(' ')}`.toLowerCase();
      return pool.includes(query);
    });
    state.paletteSelectedIndex = 0;
    renderPaletteItems(filtered);
  });

  // Backup & Import
  el.openBackupBtn.addEventListener('click', openBackupModal);
  el.closeBackupModalBtn.addEventListener('click', closeBackupModal);
  el.exportDownloadBtn.addEventListener('click', () => {
    window.location.href = 'api.php?action=export';
  });
  el.btnImportSubmit.addEventListener('click', handleImportSubmit);

  // Global Keyboard Shortcuts
  document.addEventListener('keydown', (e) => {
    // Escape to close modals
    if (e.key === 'Escape') {
      closeCommandModal();
      closeParamModal();
      closePalette();
      closeBackupModal();
    }

    // Ctrl+K or Cmd+K to trigger Command Palette
    if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
      e.preventDefault();
      if (el.paletteModal.classList.contains('active')) {
        closePalette();
      } else {
        openPalette();
      }
    }

    // '/' to search (if not already in an input)
    if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
      e.preventDefault();
      el.searchInput.focus();
      el.searchInput.select();
    }

    // Palette navigation with arrow keys & enter
    if (el.paletteModal.classList.contains('active')) {
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        state.paletteSelectedIndex = Math.min(state.paletteSelectedIndex + 1, paletteFiltered.length - 1);
        updatePaletteSelection();
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        state.paletteSelectedIndex = Math.max(state.paletteSelectedIndex - 1, 0);
        updatePaletteSelection();
      } else if (e.key === 'Enter') {
        e.preventDefault();
        const selected = paletteFiltered[state.paletteSelectedIndex];
        if (selected) {
          executeCopy(selected.command, selected.id, null);
          closePalette();
        }
      }
    }
  });

  function updatePaletteSelection() {
    const items = el.paletteResults.querySelectorAll('.palette-item');
    items.forEach((item, idx) => {
      item.classList.toggle('selected', idx === state.paletteSelectedIndex);
      if (idx === state.paletteSelectedIndex) {
        item.scrollIntoView({ block: 'nearest' });
      }
    });
  }

  // Close modals clicking on backdrop
  [el.commandModal, el.paramModal, el.paletteModal, el.backupModal].forEach(m => {
    m.addEventListener('click', (e) => {
      if (e.target === m) {
        m.classList.remove('active');
      }
    });
  });

  // Initial Boot
  fetchMetadata();
  fetchCommands();
});
