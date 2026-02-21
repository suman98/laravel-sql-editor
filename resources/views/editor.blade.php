<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SQL Analyzer</title>

    {{-- CodeMirror 5 --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/hint/show-hint.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/dracula.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/eclipse.min.css">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            background-image: radial-gradient(circle at top right, rgba(56, 189, 248, 0.12), transparent 42%);
        }

        .app-header {
            background: #1e293b;
            border-bottom: 1px solid #334155;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            backdrop-filter: blur(4px);
        }

        .app-header h1 {
            font-size: 20px;
            font-weight: 600;
            color: #f1f5f9;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .app-header h1 svg {
            width: 24px;
            height: 24px;
            color: #38bdf8;
        }

        .theme-toggle {
            background: #334155;
            color: #cbd5e1;
        }

        .theme-toggle:hover {
            background: #475569;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
        }

        .workspace-grid {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 20px;
            align-items: start;
        }

        .sidebar {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 14px;
            position: sticky;
            top: 24px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
        }

        .sidebar-title {
            font-size: 13px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 12px;
        }

        .saved-query-form {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 12px;
        }

        .saved-query-input {
            width: 100%;
            background: #0f172a;
            border: 1px solid #334155;
            color: #e2e8f0;
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 13px;
            outline: none;
        }

        .saved-query-input:focus {
            border-color: #2563eb;
        }

        .saved-query-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 420px;
            overflow-y: auto;
        }

        .saved-query-item {
            width: 100%;
            border: 1px solid #334155;
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 8px;
            padding: 8px 10px;
            text-align: left;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.15s ease;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .saved-query-item:hover {
            background: #1e293b;
            border-color: #475569;
        }

        .saved-query-item.is-active {
            background: #1d4ed8;
            border-color: #2563eb;
            color: #ffffff;
            box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.45);
        }

        .saved-query-empty {
            color: #64748b;
            font-size: 13px;
            padding: 4px 2px;
        }

        .main-content {
            min-width: 0;
        }

        /* Editor Panel */
        .editor-panel {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 24px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
        }

        .editor-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: #1e293b;
            border-bottom: 1px solid #334155;
        }

        .editor-toolbar .label {
            font-size: 13px;
            font-weight: 500;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .toolbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .title-to-query {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 12px 16px 0;
            padding: 10px;
            border: 1px solid #334155;
            border-radius: 10px;
            background: #0f172a;
        }

        .title-to-query-input {
            flex: 1;
            min-width: 160px;
            background: #1e293b;
            border: 1px solid #334155;
            color: #e2e8f0;
            border-radius: 8px;
            padding: 8px 10px;
            font-size: 13px;
            outline: none;
        }

        .title-to-query-input:focus {
            border-color: #2563eb;
        }

        .btn-generate {
            background: #0ea5e9;
            color: #ffffff;
        }

        .btn-generate:hover {
            background: #0284c7;
        }

        .btn-editor-theme {
            background: #475569;
            color: #e2e8f0;
        }

        .btn-editor-theme:hover {
            background: #64748b;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: 500;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s ease;
            transform: translateY(0);
        }

        .btn:hover:not(:disabled) {
            transform: translateY(-1px);
        }

        .btn:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .btn-primary {
            background: #2563eb;
            color: #fff;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-primary:disabled {
            background: #1e3a5f;
            color: #64748b;
            cursor: not-allowed;
        }

        .btn-clear {
            background: #334155;
            color: #cbd5e1;
        }

        .btn-clear:hover {
            background: #475569;
        }

        .btn-format {
            background: #334155;
            color: #cbd5e1;
        }

        .btn-format:hover {
            background: #475569;
        }

        .btn svg {
            width: 16px;
            height: 16px;
        }

        .CodeMirror {
            height: auto;
            min-height: 220px;
            max-height: none;
            font-size: 14px;
            line-height: 1.6;
        }

        .CodeMirror-scroll {
            min-height: 220px;
            max-height: none;
            overflow-y: hidden;
            overflow-x: auto !important;
        }

        .keyboard-hint {
            font-size: 11px;
            color: #64748b;
            padding: 0 16px 8px;
            background: #282a36;
        }

        .keyboard-hint kbd {
            background: #44475a;
            border-radius: 3px;
            padding: 1px 5px;
            font-family: inherit;
            font-size: 11px;
            color: #94a3b8;
        }

        /* Status bar */
        .status-bar {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 10px 16px;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 13px;
            color: #94a3b8;
        }

        .status-bar .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-success {
            background: #064e3b;
            color: #6ee7b7;
        }

        .badge-error {
            background: #7f1d1d;
            color: #fca5a5;
        }

        .badge-info {
            background: #1e3a5f;
            color: #93c5fd;
        }

        /* Error message */
        .error-message {
            background: #450a0a;
            border: 1px solid #7f1d1d;
            border-radius: 10px;
            padding: 14px 18px;
            margin-bottom: 24px;
            color: #fca5a5;
            font-size: 14px;
            display: none;
        }

        /* Results panel */
        .results-panel {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            overflow: hidden;
            display: none;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
        }

        .results-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            border-bottom: 1px solid #334155;
        }

        .results-header .label {
            font-size: 13px;
            font-weight: 500;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        thead th {
            background: #0f172a;
            color: #94a3b8;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.05em;
            padding: 10px 14px;
            text-align: left;
            position: sticky;
            top: 0;
            border-bottom: 1px solid #334155;
            white-space: nowrap;
        }

        tbody tr {
            border-bottom: 1px solid #1e293b;
            transition: background 0.1s ease;
        }

        tbody tr:nth-child(even) {
            background: #0f172a33;
        }

        tbody tr:hover {
            background: #334155;
        }

        tbody td {
            padding: 9px 14px;
            color: #e2e8f0;
            white-space: nowrap;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        td.null-value {
            color: #64748b;
            font-style: italic;
        }

        /* Pagination */
        .pagination-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            border-top: 1px solid #334155;
        }

        .pagination-info {
            font-size: 13px;
            color: #94a3b8;
        }

        .pagination-controls {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .pagination-controls button {
            background: #334155;
            border: none;
            color: #cbd5e1;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.15s ease;
        }

        .pagination-controls button:hover:not(:disabled) {
            background: #475569;
        }

        .pagination-controls button:disabled {
            color: #475569;
            cursor: not-allowed;
        }

        .pagination-controls button.active {
            background: #2563eb;
            color: #fff;
        }

        .page-size-select {
            background: #334155;
            border: 1px solid #475569;
            color: #cbd5e1;
            padding: 5px 8px;
            border-radius: 6px;
            font-size: 13px;
        }

        /* Loading spinner */
        .loading-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(2px);
        }

        [data-theme="light"] .loading-overlay {
            background: rgba(248, 250, 252, 0.58);
        }

        .spinner {
            width: 36px;
            height: 36px;
            border: 3px solid #334155;
            border-top-color: #2563eb;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-state svg {
            width: 48px;
            height: 48px;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 14px;
        }

        /* CodeMirror hint overrides for dark theme */
        .CodeMirror-hints {
            background: #1e293b !important;
            border: 1px solid #334155 !important;
            border-radius: 8px !important;
            box-shadow: 0 8px 24px rgba(0,0,0,0.4) !important;
            padding: 4px !important;
            z-index: 100;
        }

        .CodeMirror-hint {
            color: #e2e8f0 !important;
            padding: 4px 10px !important;
            border-radius: 4px !important;
            font-size: 13px !important;
        }

        .CodeMirror-hint-active {
            background: #2563eb !important;
            color: #fff !important;
        }

        [data-theme="light"] body {
            background: #f8fafc;
            color: #0f172a;
            background-image: radial-gradient(circle at top right, rgba(59, 130, 246, 0.12), transparent 44%);
        }

        [data-theme="light"] .app-header {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
        }

        [data-theme="light"] .app-header h1 {
            color: #0f172a;
        }

        [data-theme="light"] .theme-toggle,
        [data-theme="light"] .btn-clear,
        [data-theme="light"] .btn-format {
            background: #e2e8f0;
            color: #334155;
        }

        [data-theme="light"] .btn-editor-theme {
            background: #dbeafe;
            color: #1e3a8a;
        }

        [data-theme="light"] .btn-generate {
            background: #2563eb;
            color: #ffffff;
        }

        [data-theme="light"] .theme-toggle:hover,
        [data-theme="light"] .btn-clear:hover,
        [data-theme="light"] .btn-format:hover {
            background: #cbd5e1;
        }

        [data-theme="light"] .editor-panel,
        [data-theme="light"] .sidebar,
        [data-theme="light"] .status-bar,
        [data-theme="light"] .results-panel {
            background: #ffffff;
            border-color: #e2e8f0;
        }

        [data-theme="light"] .editor-toolbar,
        [data-theme="light"] .results-header,
        [data-theme="light"] .pagination-bar {
            background: #f8fafc;
            border-color: #e2e8f0;
        }

        [data-theme="light"] .editor-toolbar .label,
        [data-theme="light"] .sidebar-title,
        [data-theme="light"] .results-header .label,
        [data-theme="light"] .pagination-info,
        [data-theme="light"] .status-bar,
        [data-theme="light"] .empty-state,
        [data-theme="light"] td.null-value {
            color: #64748b;
        }

        [data-theme="light"] .saved-query-input {
            background: #ffffff;
            color: #0f172a;
            border-color: #cbd5e1;
        }

        [data-theme="light"] .title-to-query {
            background: #f8fafc;
            border-color: #e2e8f0;
        }

        [data-theme="light"] .title-to-query-input {
            background: #ffffff;
            color: #0f172a;
            border-color: #cbd5e1;
        }

        [data-theme="light"] .saved-query-item {
            background: #f8fafc;
            color: #334155;
            border-color: #e2e8f0;
        }

        [data-theme="light"] .saved-query-item:hover {
            background: #eef2ff;
            border-color: #cbd5e1;
        }

        [data-theme="light"] .saved-query-item.is-active {
            background: #dbeafe;
            border-color: #93c5fd;
            color: #1e3a8a;
            box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.25);
        }

        [data-theme="light"] .saved-query-empty {
            color: #94a3b8;
        }

        [data-theme="light"] .keyboard-hint {
            background: #f1f5f9;
            color: #64748b;
        }

        [data-theme="light"] .keyboard-hint kbd {
            background: #e2e8f0;
            color: #334155;
        }

        [data-theme="light"] .error-message {
            background: #fee2e2;
            border-color: #fecaca;
            color: #991b1b;
        }

        [data-theme="light"] thead th {
            background: #f8fafc;
            color: #64748b;
            border-bottom-color: #e2e8f0;
        }

        [data-theme="light"] tbody tr {
            border-bottom-color: #eef2f7;
        }

        [data-theme="light"] tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        [data-theme="light"] tbody tr:hover {
            background: #eef2ff;
        }

        [data-theme="light"] tbody td {
            color: #0f172a;
        }

        [data-theme="light"] .pagination-controls button,
        [data-theme="light"] .page-size-select {
            background: #e2e8f0;
            border-color: #cbd5e1;
            color: #334155;
        }

        [data-theme="light"] .pagination-controls button:hover:not(:disabled) {
            background: #cbd5e1;
        }

        [data-theme="light"] .pagination-controls button:disabled {
            color: #94a3b8;
        }

        [data-theme="light"] .spinner {
            border-color: #e2e8f0;
            border-top-color: #2563eb;
        }

        [data-theme="light"] .CodeMirror {
            background: #ffffff;
            color: #0f172a;
        }

        [data-theme="light"] .CodeMirror-gutters {
            background: #f8fafc;
            border-right: 1px solid #e2e8f0;
        }

        [data-theme="light"] .CodeMirror-hints {
            background: #ffffff !important;
            border-color: #e2e8f0 !important;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12) !important;
        }

        [data-theme="light"] .CodeMirror-hint {
            color: #334155 !important;
        }

        @media (max-width: 1024px) {
            .workspace-grid {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
            }
        }
    </style>
</head>
<body>

<header class="app-header">
    <h1>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <ellipse cx="12" cy="5" rx="9" ry="3"/>
            <path d="M3 5v14c0 1.66 4.03 3 9 3s9-1.34 9-3V5"/>
            <path d="M3 12c0 1.66 4.03 3 9 3s9-1.34 9-3"/>
        </svg>
        SQL Analyzer
    </h1>
    <div class="header-actions">
        <button class="btn theme-toggle" id="btn-theme" title="Toggle application theme">Light Mode</button>
    </div>
</header>

<div class="container">

    <div class="workspace-grid">
    <aside class="sidebar">
        <div class="sidebar-title">Saved Queries</div>
        <div class="saved-query-form">
            <input type="text" id="query-name" class="saved-query-input" placeholder="Enter query name">
            <button class="btn btn-clear" id="btn-save-query" type="button" disabled>Save Query</button>
        </div>
        <ul id="saved-query-list" class="saved-query-list"></ul>
        <div id="saved-query-empty" class="saved-query-empty">No saved queries yet.</div>
    </aside>

    <main class="main-content">

    {{-- Editor --}}
    <div class="editor-panel">
        <div class="editor-toolbar">
            <span class="label">SQL Editor</span>
            <div class="toolbar-actions">
                <button class="btn btn-editor-theme" id="btn-editor-theme" title="Toggle editor dark and light mode">
                    Editor: Dark
                </button>
                <button class="btn btn-format" id="btn-format" title="Format SQL (Shift+Alt+F)">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16"/><path d="M4 12h10"/><path d="M4 17h12"/></svg>
                    Format
                </button>
                <button class="btn btn-clear" id="btn-clear" title="Clear editor">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M5 6l1 14h12l1-14"/></svg>
                    Clear
                </button>
                <button class="btn btn-primary" id="btn-run" title="Run query (Ctrl+Enter)">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    Run Query
                </button>
            </div>
        </div>
        <div class="title-to-query">
            <input type="text" id="query-title" class="title-to-query-input" placeholder="Type title, e.g. Active users this month">
            <button class="btn btn-generate" id="btn-generate-query" type="button">Generate Query</button>
        </div>
        <textarea id="sql-editor"></textarea>
        <div class="keyboard-hint">
            <kbd>Ctrl</kbd>+<kbd>Enter</kbd> to run &nbsp;|&nbsp; <kbd>Ctrl</kbd>+<kbd>Space</kbd> for autocomplete &nbsp;|&nbsp; <kbd>Shift</kbd>+<kbd>Alt</kbd>+<kbd>F</kbd> to format
        </div>
    </div>

    {{-- Status bar --}}
    <div class="status-bar" id="status-bar" style="display:none;">
        <span id="status-badge"></span>
        <span id="status-rows"></span>
        <span id="status-time"></span>
    </div>

    {{-- Error --}}
    <div class="error-message" id="error-message"></div>

    {{-- Loading --}}
    <div class="loading-overlay" id="loading">
        <div class="spinner"></div>
    </div>

    {{-- Results --}}
    <div class="results-panel" id="results-panel">
        <div class="results-header">
            <span class="label">Results</span>
            <div style="display:flex;align-items:center;gap:10px;">
                <button class="btn btn-clear" id="btn-export-csv" title="Export all rows as CSV" disabled>
                    Export CSV
                </button>
                <button class="btn btn-clear" id="btn-export-json" title="Export all rows as JSON" disabled>
                    Export JSON
                </button>
                <label class="pagination-info" for="page-size">Rows per page:</label>
                <select class="page-size-select" id="page-size">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        <div class="table-wrapper">
            <table>
                <thead id="table-head"></thead>
                <tbody id="table-body"></tbody>
            </table>
        </div>
        <div class="pagination-bar">
            <span class="pagination-info" id="pagination-info"></span>
            <div class="pagination-controls" id="pagination-controls"></div>
        </div>
    </div>

    {{-- Empty state --}}
    <div class="empty-state" id="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <ellipse cx="12" cy="5" rx="9" ry="3"/>
            <path d="M3 5v14c0 1.66 4.03 3 9 3s9-1.34 9-3V5"/>
            <path d="M3 12c0 1.66 4.03 3 9 3s9-1.34 9-3"/>
        </svg>
        <p>Write an SQL query above and click <strong>Run Query</strong> to see results.</p>
    </div>

    </main>
    </div>

</div>

{{-- CodeMirror 5 + SQL mode + hint addon --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/sql/sql.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/hint/show-hint.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/hint/sql-hint.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/edit/matchbrackets.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/edit/closebrackets.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sql-formatter@15.4.2/dist/sql-formatter.min.js"></script>

<script>
(function () {
    // ── State ──────────────────────────────────────────────────────
    let allData = [];
    let currentPage = 1;
    let pageSize = 25;
    let schemaHints = {};
    let savedQueries = [];
    let selectedSavedQueryId = null;
    let editorTheme = 'dracula';
    let activeBackendCalls = 0;

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const savedQueriesIndexUrl = "{{ route('sql-analyzer.saved-queries.index') }}";
    const savedQueriesStoreUrl = "{{ route('sql-analyzer.saved-queries.store') }}";
    const savedQueriesShowUrlTemplate = "{{ route('sql-analyzer.saved-queries.show', ['id' => '__ID__']) }}";

    // ── DOM refs ───────────────────────────────────────────────────
    const btnRun        = document.getElementById('btn-run');
    const btnTheme      = document.getElementById('btn-theme');
    const btnEditorTheme = document.getElementById('btn-editor-theme');
    const btnGenerateQuery = document.getElementById('btn-generate-query');
    const btnFormat     = document.getElementById('btn-format');
    const btnClear      = document.getElementById('btn-clear');
    const btnSaveQuery  = document.getElementById('btn-save-query');
    const btnExportCsv  = document.getElementById('btn-export-csv');
    const btnExportJson = document.getElementById('btn-export-json');
    const queryTitleInput = document.getElementById('query-title');
    const queryNameInput = document.getElementById('query-name');
    const savedQueryList = document.getElementById('saved-query-list');
    const savedQueryEmpty = document.getElementById('saved-query-empty');
    const statusBar     = document.getElementById('status-bar');
    const statusBadge   = document.getElementById('status-badge');
    const statusRows    = document.getElementById('status-rows');
    const statusTime    = document.getElementById('status-time');
    const errorDiv      = document.getElementById('error-message');
    const loadingDiv    = document.getElementById('loading');
    const resultsPanel  = document.getElementById('results-panel');
    const tableHead     = document.getElementById('table-head');
    const tableBody     = document.getElementById('table-body');
    const paginationInfo = document.getElementById('pagination-info');
    const paginationCtrl = document.getElementById('pagination-controls');
    const pageSizeSelect = document.getElementById('page-size');
    const emptyState    = document.getElementById('empty-state');

    function getInitialTheme() {
        const storedTheme = localStorage.getItem('sql-analyzer-theme');

        if (storedTheme === 'dark' || storedTheme === 'light') {
            return storedTheme;
        }

        return window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
    }

    function setDocumentTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        btnTheme.textContent = theme === 'light' ? 'Dark Mode' : 'Light Mode';
    }

    const initialTheme = getInitialTheme();
    setDocumentTheme(initialTheme);

    function getInitialEditorTheme() {
        const stored = localStorage.getItem('sql-analyzer-editor-theme');
        if (stored === 'dracula' || stored === 'eclipse') {
            return stored;
        }
        return 'dracula';
    }

    function setEditorTheme(theme) {
        editorTheme = theme;
        btnEditorTheme.textContent = theme === 'eclipse' ? 'Editor: Light' : 'Editor: Dark';
        if (typeof editor !== 'undefined') {
            editor.setOption('theme', theme);
            refreshEditorHeight();
        }
    }

    function startBackendLoading() {
        activeBackendCalls += 1;
        loadingDiv.style.display = 'flex';
    }

    function stopBackendLoading() {
        activeBackendCalls = Math.max(0, activeBackendCalls - 1);
        if (activeBackendCalls === 0) {
            loadingDiv.style.display = 'none';
        }
    }

    async function withBackendLoading(action) {
        startBackendLoading();
        try {
            return await action();
        } finally {
            stopBackendLoading();
        }
    }

    function updateSaveQueryState() {
        const hasName = queryNameInput.value.trim().length > 0;
        const hasSql = editor && editor.getValue().trim().length > 0;
        btnSaveQuery.disabled = !(hasName && hasSql);
    }

    async function loadSavedQueries() {
        try {
            await withBackendLoading(async () => {
                const response = await fetch(savedQueriesIndexUrl, {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) {
                    throw new Error('Failed to fetch saved queries.');
                }

                const body = await response.json();
                savedQueries = Array.isArray(body.data) ? body.data : [];
            });
        } catch (error) {
            savedQueries = [];
        }
    }

    function renderSavedQueries() {
        if (!savedQueries.length) {
            savedQueryList.innerHTML = '';
            savedQueryEmpty.style.display = 'block';
            return;
        }

        savedQueryEmpty.style.display = 'none';
        savedQueryList.innerHTML = savedQueries.map((item) =>
            '<li><button class="saved-query-item ' + (String(selectedSavedQueryId) === String(item.id) ? 'is-active' : '') + '" type="button" data-query-id="' + escapeAttr(String(item.id)) + '" title="' + escapeAttr(item.name) + '">' + escapeHtml(item.name) + '</button></li>'
        ).join('');
    }

    async function saveCurrentQuery() {
        const sql = editor.getValue().trim();
        const name = queryNameInput.value.trim();

        if (!name || !sql) {
            return;
        }

        try {
            btnSaveQuery.disabled = true;

            await withBackendLoading(async () => {
                const response = await fetch(savedQueriesStoreUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ name, sql })
                });

                if (!response.ok) {
                    const body = await response.json().catch(() => ({}));
                    throw new Error(body.error || 'Unable to save query.');
                }
            });

            await loadSavedQueries();
            renderSavedQueries();
            queryNameInput.value = '';
            updateSaveQueryState();
        } catch (error) {
            errorDiv.textContent = error.message || 'Unable to save query.';
            errorDiv.style.display = 'block';
            updateSaveQueryState();
        }
    }

    function getSavedQueryShowUrl(id) {
        return savedQueriesShowUrlTemplate.replace('__ID__', encodeURIComponent(String(id)));
    }

    async function loadSavedQueryById(id) {
        try {
            const query = await withBackendLoading(async () => {
                const response = await fetch(getSavedQueryShowUrl(id), {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) {
                    throw new Error('Unable to load saved query.');
                }

                const body = await response.json();
                return body.data;
            });

            if (!query || typeof query.sql !== 'string') {
                throw new Error('Saved query payload is invalid.');
            }

            selectedSavedQueryId = query.id;
            renderSavedQueries();
            editor.setValue(query.sql);
            refreshEditorHeight();
            editor.focus();
        } catch (error) {
            errorDiv.textContent = error.message || 'Unable to load saved query.';
            errorDiv.style.display = 'block';
        }
    }

    // ── CodeMirror ─────────────────────────────────────────────────
    const editor = CodeMirror.fromTextArea(document.getElementById('sql-editor'), {
        mode: 'text/x-sql',
        theme: getInitialEditorTheme(),
        lineNumbers: true,
        matchBrackets: true,
        autoCloseBrackets: true,
        indentWithTabs: true,
        smartIndent: true,
        lineWrapping: true,
        hintOptions: {
            completeSingle: false,
            tables: {}
        },
        extraKeys: {
            'Ctrl-Space': 'autocomplete',
            'Ctrl-Enter': runQuery,
            'Cmd-Enter': runQuery,
            'Shift-Alt-F': formatSQL,
            'Shift-Cmd-F': formatSQL
        }
    });

    function refreshEditorHeight() {
        editor.setSize(null, 'auto');
        editor.refresh();
    }

    refreshEditorHeight();
    editor.on('change', function () {
        refreshEditorHeight();
        updateSaveQueryState();
    });
    setEditorTheme(getInitialEditorTheme());
    updateSaveQueryState();

    loadSavedQueries().then(renderSavedQueries);

    // trigger autocomplete on key input
    editor.on('inputRead', function (cm, change) {
        if (change.origin !== '+input') return;
        const ch = change.text[0];
        if (/\w|\./.test(ch)) {
            cm.showHint({ completeSingle: false });
        }
    });

    // ── Load DB schema for autocomplete ────────────────────────────
    async function loadSchemaHints() {
        try {
            const data = await withBackendLoading(async () => {
                const response = await fetch("{{ route('sql-analyzer.schema') }}", {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) {
                    throw new Error('Unable to load schema hints.');
                }

                return await response.json();
            });

            if (data.schema) {
                schemaHints = data.schema;
                editor.setOption('hintOptions', {
                    completeSingle: false,
                    tables: schemaHints
                });
            }
        } catch (error) {
        }
    }

    loadSchemaHints();

    function normalizeText(text) {
        return String(text || '').toLowerCase().replace(/[^a-z0-9_ ]+/g, ' ').replace(/\s+/g, ' ').trim();
    }

    function singularize(word) {
        if (word.endsWith('ies')) return word.slice(0, -3) + 'y';
        if (word.endsWith('ses')) return word.slice(0, -2);
        if (word.endsWith('s') && word.length > 3) return word.slice(0, -1);
        return word;
    }

    function pickBestTableFromTitle(title) {
        const words = normalizeText(title).split(' ').filter(Boolean);
        const tableNames = Object.keys(schemaHints || {});
        if (!tableNames.length) return null;

        let bestTable = tableNames[0];
        let bestScore = -1;

        tableNames.forEach((table) => {
            const normalizedTable = normalizeText(table);
            const tableTokens = normalizedTable.split(/[_\s]+/).filter(Boolean);
            let score = 0;

            words.forEach((word) => {
                const singularWord = singularize(word);
                if (normalizedTable.includes(word)) score += 3;
                if (normalizedTable.includes(singularWord)) score += 2;
                tableTokens.forEach((token) => {
                    if (token === word || token === singularWord) score += 4;
                    if (token.includes(word) || token.includes(singularWord)) score += 1;
                });
            });

            if (score > bestScore) {
                bestScore = score;
                bestTable = table;
            }
        });

        return bestScore > 0 ? bestTable : tableNames[0];
    }

    function pickColumns(table) {
        const columns = schemaHints[table] || [];
        const preferred = ['id', 'name', 'title', 'email', 'status', 'created_at', 'updated_at'];
        const selected = preferred.filter((column) => columns.includes(column));
        if (selected.length >= 3) return selected.slice(0, 6);
        return columns.slice(0, Math.min(6, columns.length));
    }

    function extractLimitFromTitle(title) {
        const match = normalizeText(title).match(/\b(\d{1,4})\b/);
        if (!match) return 50;
        const parsed = parseInt(match[1], 10);
        if (Number.isNaN(parsed)) return 50;
        return Math.max(1, Math.min(parsed, 1000));
    }

    function generateQueryFromTitle() {
        const title = queryTitleInput.value.trim();
        if (!title) return;

        const table = pickBestTableFromTitle(title);
        if (!table) return;

        const columns = pickColumns(table);
        const normalizedTitle = normalizeText(title);
        const limit = extractLimitFromTitle(title);
        const tableColumns = schemaHints[table] || [];
        const hasCreatedAt = tableColumns.includes('created_at');

        let sql;
        if (/\b(count|total|how many)\b/.test(normalizedTitle)) {
            sql = 'SELECT COUNT(*) AS total\nFROM ' + table + ';';
        } else {
            const selectColumns = columns.length ? columns.join(', ') : '*';
            sql = 'SELECT ' + selectColumns + '\nFROM ' + table;

            if (/\b(active)\b/.test(normalizedTitle)) {
                if (tableColumns.includes('is_active')) {
                    sql += '\nWHERE is_active = 1';
                } else if (tableColumns.includes('status')) {
                    sql += "\nWHERE status = 'active'";
                }
            }

            if (/\b(latest|recent|newest|last)\b/.test(normalizedTitle) && hasCreatedAt) {
                sql += '\nORDER BY created_at DESC';
            }

            sql += '\nLIMIT ' + limit + ';';
        }

        editor.setValue(sql);
        refreshEditorHeight();
        editor.focus();
    }

    // ── Run query ──────────────────────────────────────────────────
    async function runQuery() {
        const sql = editor.getValue().trim();
        if (!sql) return;

        // Reset UI
        errorDiv.style.display = 'none';
        resultsPanel.style.display = 'none';
        emptyState.style.display = 'none';
        statusBar.style.display = 'none';
        btnExportCsv.disabled = true;
        btnExportJson.disabled = true;
        btnRun.disabled = true;

        try {
            const { ok, body } = await withBackendLoading(async () => {
                const response = await fetch("{{ route('sql-analyzer.execute') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ sql })
                });

                const body = await response.json();
                return { ok: response.ok, body };
            });

            if (!ok || body.error) {
                errorDiv.textContent = body.error || 'An unknown error occurred.';
                errorDiv.style.display = 'block';
                statusBar.style.display = 'flex';
                statusBadge.innerHTML = '<span class="badge badge-error">Error</span>';
                statusRows.textContent = '';
                statusTime.textContent = '';
                btnExportCsv.disabled = true;
                btnExportJson.disabled = true;
                return;
            }

            allData = body.data || [];
            currentPage = 1;

            statusBar.style.display = 'flex';
            statusBadge.innerHTML = '<span class="badge badge-success">Success</span>';
            statusRows.textContent = allData.length + ' row' + (allData.length !== 1 ? 's' : '') + ' returned';
            statusTime.textContent = body.responseTime + ' ms';

            if (allData.length === 0) {
                emptyState.style.display = 'block';
                emptyState.querySelector('p').textContent = 'Query executed successfully but returned no rows.';
                btnExportCsv.disabled = true;
                btnExportJson.disabled = true;
                return;
            }

            btnExportCsv.disabled = false;
            btnExportJson.disabled = false;

            renderTable();
        } catch (err) {
            errorDiv.textContent = 'Network error: ' + err.message;
            errorDiv.style.display = 'block';
            btnExportCsv.disabled = true;
            btnExportJson.disabled = true;
        } finally {
            btnRun.disabled = false;
        }
    }

    // ── Render table ───────────────────────────────────────────────
    function renderTable() {
        if (allData.length === 0) return;

        const columns = Object.keys(allData[0]);
        const totalPages = Math.ceil(allData.length / pageSize);
        if (currentPage > totalPages) currentPage = totalPages;

        // Head
        tableHead.innerHTML = '<tr>' + columns.map(c =>
            '<th>' + escapeHtml(c) + '</th>'
        ).join('') + '</tr>';

        // Body (current page)
        const start = (currentPage - 1) * pageSize;
        const pageData = allData.slice(start, start + pageSize);

        tableBody.innerHTML = pageData.map(row =>
            '<tr>' + columns.map(c => {
                const val = row[c];
                if (val === null || val === undefined) {
                    return '<td class="null-value">NULL</td>';
                }
                return '<td title="' + escapeAttr(String(val)) + '">' + escapeHtml(String(val)) + '</td>';
            }).join('') + '</tr>'
        ).join('');

        // Pagination info
        const end = Math.min(start + pageSize, allData.length);
        paginationInfo.textContent = 'Showing ' + (start + 1) + '–' + end + ' of ' + allData.length;

        // Pagination controls
        renderPaginationControls(totalPages);

        resultsPanel.style.display = 'block';
    }

    // ── Pagination controls ────────────────────────────────────────
    function renderPaginationControls(totalPages) {
        paginationCtrl.innerHTML = '';

        if (totalPages <= 1) return;

        // Prev
        addPageButton('«', currentPage - 1, currentPage === 1);

        // Page numbers (show max 7 with ellipsis)
        const pages = getVisiblePages(currentPage, totalPages, 7);
        pages.forEach(p => {
            if (p === '...') {
                const span = document.createElement('span');
                span.textContent = '…';
                span.style.padding = '6px 4px';
                span.style.color = '#64748b';
                paginationCtrl.appendChild(span);
            } else {
                addPageButton(p, p, false, p === currentPage);
            }
        });

        // Next
        addPageButton('»', currentPage + 1, currentPage === totalPages);
    }

    function addPageButton(label, page, disabled, active) {
        const btn = document.createElement('button');
        btn.textContent = label;
        btn.disabled = !!disabled;
        if (active) btn.classList.add('active');
        btn.addEventListener('click', () => {
            currentPage = page;
            renderTable();
        });
        paginationCtrl.appendChild(btn);
    }

    function getVisiblePages(current, total, maxVisible) {
        if (total <= maxVisible) {
            return Array.from({ length: total }, (_, i) => i + 1);
        }

        const pages = [];
        const half = Math.floor(maxVisible / 2);
        let start = Math.max(2, current - half);
        let end = Math.min(total - 1, current + half);

        if (current - half < 2) end = Math.min(total - 1, maxVisible - 1);
        if (current + half > total - 1) start = Math.max(2, total - maxVisible + 2);

        pages.push(1);
        if (start > 2) pages.push('...');
        for (let i = start; i <= end; i++) pages.push(i);
        if (end < total - 1) pages.push('...');
        pages.push(total);

        return pages;
    }

    // ── Page size change ───────────────────────────────────────────
    pageSizeSelect.addEventListener('change', function () {
        pageSize = parseInt(this.value, 10);
        currentPage = 1;
        if (allData.length > 0) renderTable();
    });

    // ── Format SQL ────────────────────────────────────────────────
    function formatSQL() {
        const sql = editor.getValue().trim();
        if (!sql) return;
        try {
            const formatted = sqlFormatter.format(sql, {
                language: 'sql',
                tabWidth: 2,
                keywordCase: 'upper',
                linesBetweenQueries: 2
            });
            editor.setValue(formatted);
        } catch (e) {
            // silently ignore formatting errors
        }
    }

    function exportToJson() {
        if (!allData.length) return;

        const fileContent = JSON.stringify(allData, null, 2);
        downloadFile(fileContent, 'application/json;charset=utf-8;', 'query-results.json');
    }

    function exportToCsv() {
        if (!allData.length) return;

        const columns = Object.keys(allData[0]);
        const header = columns.join(',');
        const rows = allData.map((row) => columns.map((column) => toCsvCell(row[column])).join(','));
        const csvContent = [header, ...rows].join('\n');

        downloadFile(csvContent, 'text/csv;charset=utf-8;', 'query-results.csv');
    }

    function toCsvCell(value) {
        if (value === null || value === undefined) {
            return '""';
        }

        const normalized = String(value).replace(/"/g, '""').replace(/\r?\n|\r/g, ' ');
        return '"' + normalized + '"';
    }

    function downloadFile(content, mimeType, filename) {
        const blob = new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');

        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        URL.revokeObjectURL(url);
    }

    // ── Button handlers ────────────────────────────────────────────
    btnRun.addEventListener('click', runQuery);
    btnTheme.addEventListener('click', function () {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        const nextTheme = currentTheme === 'light' ? 'dark' : 'light';

        setDocumentTheme(nextTheme);
        localStorage.setItem('sql-analyzer-theme', nextTheme);
    });
    btnEditorTheme.addEventListener('click', function () {
        const nextEditorTheme = editorTheme === 'dracula' ? 'eclipse' : 'dracula';
        setEditorTheme(nextEditorTheme);
        localStorage.setItem('sql-analyzer-editor-theme', nextEditorTheme);
    });
    btnGenerateQuery.addEventListener('click', generateQueryFromTitle);
    queryTitleInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            generateQueryFromTitle();
        }
    });
    btnFormat.addEventListener('click', formatSQL);
    btnSaveQuery.addEventListener('click', saveCurrentQuery);
    queryNameInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            saveCurrentQuery();
        }
    });
    queryNameInput.addEventListener('input', updateSaveQueryState);
    savedQueryList.addEventListener('click', function (event) {
        const target = event.target;
        if (!(target instanceof HTMLElement)) return;

        const button = target.closest('[data-query-id]');
        if (!(button instanceof HTMLElement)) return;

        const id = button.getAttribute('data-query-id');
        if (!id) return;

        loadSavedQueryById(id);
    });
    btnExportCsv.addEventListener('click', exportToCsv);
    btnExportJson.addEventListener('click', exportToJson);
    btnClear.addEventListener('click', function () {
        editor.setValue('');
        editor.focus();
    });

    // ── Helpers ────────────────────────────────────────────────────
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function escapeAttr(str) {
        return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
})();
</script>
</body>
</html>
