<?php
/**
 * ==============================================================================
 * SecOps TermVault - Terminal Command & Cyber Terminology Management Platform
 * Architect / Lead: 0745
 * Organization: KoiTech
 * Operational Codename: 0745
 * Classification: Internal Cyber Operations & Productivity Suite
 * ==============================================================================
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="author" content="0745 (KoiTech)">
  <meta name="organization" content="KoiTech">
  <title>SecOps TermVault | 0745 @ KoiTech</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="icon" type="image/png" href="assets/img/logo.png">
</head>
<body>

  <div class="app-container">
    <!-- Header -->
    <header class="app-header">
      <div class="brand-section">
        <div class="brand-logo-wrapper" title="SecOps Vault | KoiTech">
          <img src="assets/img/logo.png" alt="TermVault Logo" class="brand-logo-img">
        </div>
        <div>
          <h1 class="brand-title">
            TermVault <span class="slash">//</span> SecOps Hub
            <span class="badge-org">KoiTech</span>
            <span class="badge-beta">v2.0</span>
          </h1>
          <div class="brand-subtitle">
            <span class="kali-host">0745@koitech-secops</span>:~/vault$ Quick retrieval for terminal commands, cyber terminology
          </div>
        </div>
      </div>

      <div class="header-actions">
        <button class="btn btn-outline-cyan" id="openPaletteBtn" title="Open Quick Command Palette (Ctrl+K)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <span>Command Palette</span>
          <span class="search-shortcut-badge" style="position:static; margin-left:4px;">Ctrl+K</span>
        </button>

        <button class="btn btn-secondary" id="openBackupBtn" title="Export & Import JSON Backups">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
          </svg>
          <span>Backup / Sync</span>
        </button>

        <button class="btn btn-primary" id="openNewCommandBtn" title="Store New Command or Terminology">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          <span>+ New Command / Term</span>
        </button>
      </div>
    </header>

    <!-- Metrics Grid -->
    <section class="metrics-grid">
      <div class="metric-card">
        <div class="metric-info">
          <div class="metric-label">Stored Commands & Terms</div>
          <div class="metric-value" id="metricTotal">--</div>
        </div>
        <div class="metric-icon cyan">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="4 17 10 11 4 5"></polyline>
            <line x1="12" y1="19" x2="20" y2="19"></line>
          </svg>
        </div>
      </div>

      <div class="metric-card">
        <div class="metric-info">
          <div class="metric-label">Favorites / Pinned</div>
          <div class="metric-value" id="metricFavorites">--</div>
        </div>
        <div class="metric-icon amber">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
          </svg>
        </div>
      </div>

      <div class="metric-card">
        <div class="metric-info">
          <div class="metric-label">Operational Domains</div>
          <div class="metric-value" id="metricCategories">--</div>
        </div>
        <div class="metric-icon green">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
          </svg>
        </div>
      </div>

      <div class="metric-card">
        <div class="metric-info">
          <div class="metric-label">Executions / Copied</div>
          <div class="metric-value" id="metricCopies">--</div>
        </div>
        <div class="metric-icon purple">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
          </svg>
        </div>
      </div>
    </section>

    <!-- Toolbar: Search & Filters -->
    <section class="toolbar-section">
      <!-- Search Row -->
      <div class="search-row">
        <div class="search-wrapper">
          <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input 
            type="text" 
            id="searchInput" 
            class="search-input" 
            placeholder="Search commands, flags, terminology, tags (e.g. 'agy', 'nmap', 'reverse shell', 'privesc')..." 
            autocomplete="off" 
            spellcheck="false"
          >
          <button id="searchClearBtn" class="search-clear-btn" title="Clear search">✕</button>
          <span class="search-shortcut-badge">Press /</span>
        </div>
      </div>

      <!-- Categories Pills -->
      <div class="category-tabs" id="categoryTabs">
        <!-- Injected dynamically via app.js -->
      </div>

      <!-- Subrow Filters & View toggles -->
      <div class="filter-subrow">
        <div class="filter-left-actions">
          <button class="filter-pill-btn" id="favFilterBtn">
            <span>★</span> Favorites Only
          </button>

          <div id="activeTagFilterWrapper" class="active-tag-filter" style="display:none;">
            <span id="activeTagName">#tag</span>
            <button id="clearTagFilterBtn" title="Remove tag filter">✕</button>
          </div>
        </div>

        <div class="filter-right-actions">
          <select id="sortSelect" class="sort-select">
            <option value="most_used">Sort: Most Used 🔥</option>
            <option value="recent">Sort: Recently Updated ⏱️</option>
            <option value="created">Sort: Recently Added 🆕</option>
            <option value="alpha">Sort: Alphabetical (A-Z)</option>
          </select>

          <div class="view-toggle">
            <button id="viewGridBtn" class="active" title="Grid View">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
              </svg>
            </button>
            <button id="viewListBtn" title="List View">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="8" y1="6" x2="21" y2="6"></line>
                <line x1="8" y1="12" x2="21" y2="12"></line>
                <line x1="8" y1="18" x2="21" y2="18"></line>
                <line x1="3" y1="6" x2="3.01" y2="6"></line>
                <line x1="3" y1="12" x2="3.01" y2="12"></line>
                <line x1="3" y1="18" x2="3.01" y2="18"></line>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- Main Commands Container -->
    <main>
      <div id="commandsGrid" class="commands-grid">
        <!-- Rendered via app.js -->
      </div>

      <!-- Empty State -->
      <div id="emptyState" class="empty-state" style="display: none;">
        <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <h3 class="empty-state-title">No Matching Commands or Terms Found</h3>
        <p class="empty-state-text">Try adjusting your search query, clearing filters, or store a new command right now.</p>
        <button class="btn btn-primary" onclick="document.getElementById('openNewCommandBtn').click()">
          + Add New Command
        </button>
      </div>
    </main>

    <!-- Operational Signature Footer -->
    <footer class="app-footer">
      <div class="footer-left">
        <span class="footer-brand">SecOps TermVault</span>
        <span class="footer-sep">//</span>
        <span>Lead Architect: <strong class="signature-tag">0745</strong></span>
        <span class="footer-sep">//</span>
        <span>Organization: <strong class="signature-tag">KoiTech</strong></span>
      </div>
      <div class="footer-right">
        <span class="status-indicator-dot"></span>
        <span>SecOps Node: Active &bull; Operative 0745</span>
      </div>
    </footer>
  </div>

  <!-- MODAL 1: Add / Edit Command -->
  <div class="modal-overlay" id="commandModal">
    <div class="modal-dialog">
      <div class="modal-header">
        <h3 class="modal-title" id="modalTitle">Add Terminal Code / Terminology</h3>
        <button class="modal-close-btn" id="closeCommandModalBtn">✕</button>
      </div>
      <form id="commandForm">
        <div class="modal-body">
          <input type="hidden" id="cmdId">

          <div class="form-group">
            <label class="form-label" for="cmdTitle">Command Title / Description Name *</label>
            <input type="text" id="cmdTitle" class="form-input" placeholder="e.g. Start Antigravity (AGY) CLI" required>
            <span class="form-hint">A clear name that describes what this command achieves or what the concept is.</span>
          </div>

          <div class="form-group">
            <label class="form-label" for="cmdCommand">Terminal Code / Command / Snippet *</label>
            <textarea id="cmdCommand" class="form-textarea code-font" placeholder="e.g. agy OR nmap -sS -p- <target_ip>" required></textarea>
            <span class="form-hint">Tip: Use <code>&lt;placeholder&gt;</code> (like <code>&lt;target_ip&gt;</code> or <code>&lt;port&gt;</code>) to activate the interactive parameter builder!</span>
          </div>

          <div class="form-row-2">
            <div class="form-group">
              <label class="form-label" for="cmdCategory">Category / Domain</label>
              <select id="cmdCategory" class="form-select"></select>
            </div>

            <div class="form-group">
              <label class="form-label" for="cmdRisk">Risk / Impact Level</label>
              <select id="cmdRisk" class="form-select">
                <option value="safe">🟢 Safe (Read-only / Startup / Diagnostic)</option>
                <option value="notice">🔵 Notice (Network Probe / Active Service)</option>
                <option value="elevated">🟡 Elevated (Privileged / System Config)</option>
                <option value="dangerous">🔴 Dangerous (Wipe / Exploit / Destructive)</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="cmdTags">Tags (Comma Separated)</label>
            <input type="text" id="cmdTags" class="form-input" placeholder="e.g. antigravity, agy, kali, startup">
          </div>

          <div class="form-group">
            <label class="form-label" for="cmdDesc">Usage Notes / Flag Explanations / Syntax</label>
            <textarea id="cmdDesc" class="form-textarea" placeholder="e.g. Flags: -sS = Stealth SYN, -p- = All 65535 ports. Run as root or with sudo."></textarea>
          </div>

          <div class="form-group" style="flex-direction:row; align-items:center; gap:0.5rem;">
            <input type="checkbox" id="cmdFavorite" style="accent-color: var(--accent-amber); cursor: pointer; width: 16px; height: 16px;">
            <label for="cmdFavorite" style="font-size:0.85rem; color: var(--text-main); cursor: pointer;">
              Pin to Favorites (Starred)
            </label>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" id="cancelCommandModalBtn">Cancel</button>
          <button type="submit" class="btn btn-primary">Save to Vault</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL 2: Parameter Injector Modal -->
  <div class="modal-overlay" id="paramModal">
    <div class="modal-dialog">
      <div class="modal-header">
        <h3 class="modal-title">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
          </svg>
          Parameter Injector
        </h3>
        <button class="modal-close-btn" id="closeParamModalBtn">✕</button>
      </div>
      <div class="modal-body">
        <p style="font-size:0.85rem; color: var(--text-muted);">
          Fill in values below to dynamically customize this command for your current target:
        </p>

        <div id="paramFieldsContainer" style="display:flex; flex-direction:column; gap:0.75rem;">
          <!-- Dynamically populated -->
        </div>

        <div class="form-group" style="margin-top:0.5rem;">
          <label class="form-label">Ready-to-Execute Terminal Command</label>
          <div id="paramPreviewBox" class="param-preview-box"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="copyParamCommandBtn">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
          </svg>
          Copy Prepared Command
        </button>
      </div>
    </div>
  </div>

  <!-- MODAL 3: Spotlight / Command Palette (Ctrl+K) -->
  <div class="modal-overlay" id="paletteModal">
    <div class="modal-dialog palette-dialog">
      <div class="palette-search-wrapper">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="4 17 10 11 4 5"></polyline>
          <line x1="12" y1="19" x2="20" y2="19"></line>
        </svg>
        <input type="text" id="paletteInput" class="palette-input" placeholder="Type to search and press Enter to copy..." autocomplete="off">
        <span class="search-shortcut-badge">ESC to close</span>
      </div>
      <div class="palette-results" id="paletteResults">
        <!-- Rendered via app.js -->
      </div>
    </div>
  </div>

  <!-- MODAL 4: Backup & Sync Modal -->
  <div class="modal-overlay" id="backupModal">
    <div class="modal-dialog">
      <div class="modal-header">
        <h3 class="modal-title">Data Backup & Restore</h3>
        <button class="modal-close-btn" id="closeBackupModalBtn">✕</button>
      </div>
      <div class="modal-body">
        <div style="background: rgba(0, 240, 255, 0.05); border: 1px solid rgba(0, 240, 255, 0.2); padding: 1rem; border-radius: var(--radius-sm);">
          <h4 style="font-size: 0.9rem; margin-bottom: 0.3rem; color: var(--accent-cyan);">Export Vault Backup</h4>
          <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.75rem;">
            Download all stored commands, shortcuts, categories, and usage stats as a clean JSON file to backup or transfer between Kali boxes.
          </p>
          <button type="button" class="btn btn-outline-cyan" id="exportDownloadBtn">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
              <polyline points="7 10 12 15 17 10"></polyline>
              <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
            Download Backup (.JSON)
          </button>
        </div>

        <div style="border-top: 1px solid var(--border-color); padding-top: 1rem;">
          <h4 style="font-size: 0.9rem; margin-bottom: 0.3rem; color: #fff;">Import Commands</h4>
          <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.75rem;">
            Upload a JSON file or paste JSON data to import commands into your vault:
          </p>

          <div class="form-group" style="margin-bottom: 0.75rem;">
            <label class="form-label">Upload JSON File</label>
            <input type="file" id="importFileInput" accept=".json" class="form-input">
          </div>

          <div class="form-group" style="margin-bottom: 0.75rem;">
            <label class="form-label">Or Paste JSON Data</label>
            <textarea id="importPasteTextarea" class="form-textarea code-font" placeholder='[{"title":"...","command":"..."}]' style="height:70px;"></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Import Mode</label>
            <select id="importModeSelect" class="form-select">
              <option value="merge">Merge (Keep existing, add new / update)</option>
              <option value="replace">Replace (Overwrite vault completely)</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" id="btnImportSubmit">Start Import</button>
      </div>
    </div>
  </div>

  <!-- Toast Container -->
  <div class="toast-container" id="toastContainer"></div>

  <!-- Scripts -->
  <script src="assets/js/app.js"></script>
</body>
</html>
