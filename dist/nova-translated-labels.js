(() => {
  const MARKED = /\u2063 \(([^)\u2063]*)\)\u2063/g;
  const HAS_MARKER = '\u2063';
  const SUBTITLE_CONTEXTS = '[dusk="sidebar-menu"], th, label, h1, h2, h3, h4, [dusk="filter-menu"]';
  const UNTOUCHED = 'option, script, style, textarea';
  const ATTRIBUTES = ['placeholder', 'title', 'aria-label'];
  const SUBTITLE_CLASS = 'ntl-subtitle';

  const style = document.createElement('style');
  style.textContent = `
    .${SUBTITLE_CLASS} { display: block; unicode-bidi: isolate; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 11px; line-height: 1.3; font-weight: 400; letter-spacing: normal; text-transform: none; color: rgb(156 163 175); user-select: all; }
    .dark .${SUBTITLE_CLASS} { color: rgb(107 114 128); }
    .ntl-stack { flex-direction: column !important; align-items: flex-start !important; justify-content: center !important; }
  `;
  document.head.appendChild(style);

  const strip = (text) => text.replace(MARKED, '');

  const subtitleAfter = (node) => {
    const next = node.nextSibling;

    if (next instanceof HTMLElement && next.classList.contains(SUBTITLE_CLASS)) {
      return next;
    }

    const subtitle = document.createElement('span');
    subtitle.className = SUBTITLE_CLASS;
    node.after(subtitle);

    return subtitle;
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
      return;
    }

    const realNames = [...text.matchAll(MARKED)].map((match) => match[1]);
    node.nodeValue = strip(text);

    if (realNames.length === 0 || !parent.closest(SUBTITLE_CONTEXTS)) {
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
        processText(mutation.target);
      } else if (mutation.type === 'attributes') {
        processAttributes(mutation.target);
      } else {
        mutation.addedNodes.forEach(processTree);
      }
    }

    cleanTitle();
  });

  const start = () => {
    processTree(document.body);
    cleanTitle();
    observer.observe(document.body, { childList: true, subtree: true, characterData: true, attributes: true, attributeFilter: ATTRIBUTES });
    new MutationObserver(cleanTitle).observe(document.head, { childList: true, subtree: true, characterData: true });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
