(() => {
  const MARKED = /\u2063 \(([^\u2063]*)\)\u2063/g;
  const HAS_MARKER = '\u2063';
  const SUBTITLE_CONTEXTS = '[dusk="sidebar-menu"], th, label, h1, h2, h3, h4, [dusk="filter-menu"]';
  const UNTOUCHED = 'option, script, style, textarea';
  const ATTRIBUTES = ['placeholder', 'title', 'aria-label'];
  const SUBTITLE_CLASS = 'ntl-subtitle';

  const style = document.createElement('style');
  style.textContent = `
    .${SUBTITLE_CLASS} { display: block; unicode-bidi: isolate; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 11px; line-height: 1.3; font-weight: 400; letter-spacing: normal; text-transform: none; color: rgba(var(--colors-gray-400)); user-select: all; }
    .dark .${SUBTITLE_CLASS} { color: rgba(var(--colors-gray-500)); }
    .ntl-stack { flex-direction: column !important; align-items: flex-start !important; justify-content: center !important; }
  `;
  document.head.appendChild(style);

  const strip = (text) => text.replace(MARKED, '');

  // Each text node's subtitle, and how many of the script's own writes to it the observer has yet to report.
  // Counting writes, not comparing text, tells them apart from Vue rewriting a label with the same text.
  const subtitles = new WeakMap();
  const pendingWrites = new WeakMap();
  let observing = false;

  const subtitleAfter = (node) => {
    const existing = subtitles.get(node);

    if (existing && existing.isConnected) {
      return existing;
    }

    const subtitle = document.createElement('span');
    subtitle.className = SUBTITLE_CLASS;
    node.after(subtitle);
    subtitles.set(node, subtitle);

    return subtitle;
  };

  const removeSubtitle = (node) => {
    const subtitle = subtitles.get(node);

    if (!subtitle) {
      return;
    }

    const parent = subtitle.parentElement;
    subtitles.delete(node);
    subtitle.remove();

    if (parent && !parent.querySelector(`:scope > .${SUBTITLE_CLASS}`)) {
      parent.classList.remove('ntl-stack');
    }
  };

  const stackParent = (parent) => {
    const onlyText = [...parent.children].every((child) => child.classList.contains(SUBTITLE_CLASS));

    if (onlyText && getComputedStyle(parent).display.includes('flex')) {
      parent.classList.add('ntl-stack');
    }
  };

  const processText = (node) => {
    const text = node.nodeValue;
    const parent = node.parentElement;

    if (!text || !text.includes(HAS_MARKER) || !parent || parent.closest(UNTOUCHED)) {
      removeSubtitle(node);

      return;
    }

    const realNames = [...text.matchAll(MARKED)].map((match) => match[1]);

    if (observing) {
      pendingWrites.set(node, (pendingWrites.get(node) ?? 0) + 1);
    }

    node.nodeValue = strip(text);

    if (realNames.length === 0 || !parent.closest(SUBTITLE_CONTEXTS)) {
      removeSubtitle(node);

      return;
    }

    subtitleAfter(node).textContent = realNames.join(' · ');
    stackParent(parent);
  };

  // Attributes cannot hold a styled line, so they only lose the real name.
  const processAttributes = (element) => {
    for (const name of ATTRIBUTES) {
      const value = element.getAttribute(name);

      if (value && value.includes(HAS_MARKER)) {
        element.setAttribute(name, strip(value));
      }
    }
  };

  const processTree = (root) => {
    if (root.nodeType === Node.TEXT_NODE) {
      processText(root);

      return;
    }

    if (!(root instanceof Element)) {
      return;
    }

    processAttributes(root);
    root.querySelectorAll(ATTRIBUTES.map((name) => `[${name}]`).join(', ')).forEach(processAttributes);

    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);

    while (walker.nextNode()) {
      processText(walker.currentNode);
    }
  };

  const cleanTitle = () => {
    if (document.title.includes(HAS_MARKER)) {
      document.title = strip(document.title);
    }
  };

  const observer = new MutationObserver((mutations) => {
    for (const mutation of mutations) {
      if (mutation.type === 'characterData') {
        const pending = pendingWrites.get(mutation.target) ?? 0;

        if (pending > 0) {
          pendingWrites.set(mutation.target, pending - 1);
        } else {
          processText(mutation.target);
        }
      } else if (mutation.type === 'attributes') {
        processAttributes(mutation.target);
      } else {
        mutation.removedNodes.forEach((node) => {
          // A node that was only moved is connected again by the time this runs.
          if (node.nodeType === Node.TEXT_NODE && !node.isConnected) {
            removeSubtitle(node);
          }
        });
        mutation.addedNodes.forEach(processTree);
      }
    }

    cleanTitle();
  });

  const start = () => {
    processTree(document.body);
    cleanTitle();
    observer.observe(document.body, { childList: true, subtree: true, characterData: true, attributes: true, attributeFilter: ATTRIBUTES });
    observing = true;
    new MutationObserver(cleanTitle).observe(document.head, { childList: true, subtree: true, characterData: true });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
