// packages/morph/src/dom.js
function createElement(html) {
  const template = document.createElement("template");
  template.innerHTML = html;
  return template.content.firstElementChild;
}
function textOrComment(el) {
  return el.nodeType === 3 || el.nodeType === 8;
}
var dom = {
  replace(children, old, replacement) {
    let index = children.indexOf(old);
    if (index === -1)
      throw "Cant find element in children";
    old.replaceWith(replacement);
    children[index] = replacement;
    return children;
  },
  before(children, reference, subject) {
    let index = children.indexOf(reference);
    if (index === -1)
      throw "Cant find element in children";
    reference.before(subject);
    children.splice(index, 0, subject);
    return children;
  },
  append(children, subject, appendFn) {
    let last = children[children.length - 1];
    appendFn(subject);
    children.push(subject);
    return children;
  },
  remove(children, subject) {
    let index = children.indexOf(subject);
    if (index === -1)
      throw "Cant find element in children";
    subject.remove();
    return children.filter((i) => i !== subject);
  },
  first(children) {
    return this.teleportTo(children[0]);
  },
  next(children, reference) {
    let index = children.indexOf(reference);
    if (index === -1)
      return;
    return this.teleportTo(this.teleportBack(children[index + 1]));
  },
  teleportTo(el) {
    if (!el)
      return el;
    if (el._x_teleport)
      return el._x_teleport;
    return el;
  },
  teleportBack(el) {
    if (!el)
      return el;
    if (el._x_teleportBack)
      return el._x_teleportBack;
    return el;
  }
};

// packages/morph/src/morph.js
var resolveStep = () => {
};
var logger = () => {
};
function morph(from, toHtml, options) {
  let fromEl;
  let toEl;
  let key, lookahead, updating, updated, removing, removed, adding, added;
  function assignOptions(options2 = {}) {
    let defaultGetKey = (el) => el.getAttribute("key");
    let noop = () => {
    };
    updating = options2.updating || noop;
    updated = options2.updated || noop;
    removing = options2.removing || noop;
    removed = options2.removed || noop;
    adding = options2.adding || noop;
    added = options2.added || noop;
    key = options2.key || defaultGetKey;
    lookahead = options2.lookahead || false;
  }
  function patch(from2, to) {
    if (differentElementNamesTypesOrKeys(from2, to)) {
      return patchElement(from2, to);
    }
    let updateChildrenOnly = false;
    if (shouldSkip(updating, from2, to, () => updateChildrenOnly = true))
      return;
    window.Alpine && initializeAlpineOnTo(from2, to, () => updateChildrenOnly = true);
    if (textOrComment(to)) {
      patchNodeValue(from2, to);
      updated(from2, to);
      return;
    }
    if (!updateChildrenOnly) {
      patchAttributes(from2, to);
    }
    updated(from2, to);
    patchChildren(Array.from(from2.childNodes), Array.from(to.childNodes), (toAppend) => {
      from2.appendChild(toAppend);
    });
  }
  function differentElementNamesTypesOrKeys(from2, to) {
    return from2.nodeType != to.nodeType || from2.nodeName != to.nodeName || getKey(from2) != getKey(to);
  }
  function patchElement(from2, to) {
    if (shouldSkip(removing, from2))
      return;
    let toCloned = to.cloneNode(true);
    if (shouldSkip(adding, toCloned))
      return;
    dom.replace([from2], from2, toCloned);
    removed(from2);
    added(toCloned);
  }
  function patchNodeValue(from2, to) {
    let value = to.nodeValue;
    if (from2.nodeValue !== value) {
      from2.nodeValue = value;
    }
  }
  function patchAttributes(from2, to) {
    if (from2._x_isShown && !to._x_isShown) {
      return;
    }
    if (!from2._x_isShown && to._x_isShown) {
      return;
    }
    let domAttributes = Array.from(from2.attributes);
    let toAttributes = Array.from(to.attributes);
    for (let i = domAttributes.length - 1; i >= 0; i--) {
      let name = domAttributes[i].name;
      if (!to.hasAttribute(name)) {
        from2.removeAttribute(name);
      }
    }
    for (let i = toAttributes.length - 1; i >= 0; i--) {
      let name = toAttributes[i].name;
      let value = toAttributes[i].value;
      if (from2.getAttribute(name) !== value) {
        from2.setAttribute(name, value);
      }
    }
  }
  function patchChildren(fromChildren, toChildren, appendFn) {
    let fromKeyDomNodeMap = {};
    let fromKeyHoldovers = {};
    let currentTo = dom.first(toChildren);
    let currentFrom = dom.first(fromChildren);
    while (currentTo) {
      let toKey = getKey(currentTo);
      let fromKey = getKey(currentFrom);
      if (!currentFrom) {
        if (toKey && fromKeyHoldovers[toKey]) {
          let holdover = fromKeyHoldovers[toKey];
          fromChildren = dom.append(fromChildren, holdover, appendFn);
          currentFrom = holdover;
        } else {
          if (!shouldSkip(adding, currentTo)) {
            let clone = currentTo.cloneNode(true);
            fromChildren = dom.append(fromChildren, clone, appendFn);
            added(clone);
          }
          currentTo = dom.next(toChildren, currentTo);
          continue;
        }
      }
      let isIf = (node) => node.nodeType === 8 && node.textContent === " __BLOCK__ ";
      let isEnd = (node) => node.nodeType === 8 && node.textContent === " __ENDBLOCK__ ";
      if (isIf(currentTo) && isIf(currentFrom)) {
        let newFromChildren = [];
        let appendPoint;
        let nestedIfCount = 0;
        while (currentFrom) {
          let next = dom.next(fromChildren, currentFrom);
          if (isIf(next)) {
            nestedIfCount++;
          } else if (isEnd(next) && nestedIfCount > 0) {
            nestedIfCount--;
          } else if (isEnd(next) && nestedIfCount === 0) {
            currentFrom = dom.next(fromChildren, next);
            appendPoint = next;
            break;
          }
          newFromChildren.push(next);
          currentFrom = next;
        }
        let newToChildren = [];
        nestedIfCount = 0;
        while (currentTo) {
          let next = dom.next(toChildren, currentTo);
          if (isIf(next)) {
            nestedIfCount++;
          } else if (isEnd(next) && nestedIfCount > 0) {
            nestedIfCount--;
          } else if (isEnd(next) && nestedIfCount === 0) {
            currentTo = dom.next(toChildren, next);
            break;
          }
          newToChildren.push(next);
          currentTo = next;
        }
        patchChildren(newFromChildren, newToChildren, (node) => appendPoint.before(node));
        continue;
      }
      if (currentFrom.nodeType === 1 && lookahead) {
        let nextToElementSibling = dom.next(toChildren, currentTo);
        let found = false;
        while (!found && nextToElementSibling) {
          if (currentFrom.isEqualNode(nextToElementSibling)) {
            found = true;
            [fromChildren, currentFrom] = addNodeBefore(fromChildren, currentTo, currentFrom);
            fromKey = getKey(currentFrom);
          }
          nextToElementSibling = dom.next(toChildren, nextToElementSibling);
        }
      }
      if (toKey !== fromKey) {
        if (!toKey && fromKey) {
          fromKeyHoldovers[fromKey] = currentFrom;
          [fromChildren, currentFrom] = addNodeBefore(fromChildren, currentTo, currentFrom);
          fromChildren = dom.remove(fromChildren, fromKeyHoldovers[fromKey]);
          currentFrom = dom.next(fromChildren, currentFrom);
          currentTo = dom.next(toChildren, currentTo);
          continue;
        }
        if (toKey && !fromKey) {
          if (fromKeyDomNodeMap[toKey]) {
            fromChildren = dom.replace(fromChildren, currentFrom, fromKeyDomNodeMap[toKey]);
            currentFrom = fromKeyDomNodeMap[toKey];
          }
        }
        if (toKey && fromKey) {
          let fromKeyNode = fromKeyDomNodeMap[toKey];
          if (fromKeyNode) {
            fromKeyHoldovers[fromKey] = currentFrom;
            fromChildren = dom.replace(fromChildren, currentFrom, fromKeyNode);
            currentFrom = fromKeyNode;
          } else {
            fromKeyHoldovers[fromKey] = currentFrom;
            [fromChildren, currentFrom] = addNodeBefore(fromChildren, currentTo, currentFrom);
            fromChildren = dom.remove(fromChildren, fromKeyHoldovers[fromKey]);
            currentFrom = dom.next(fromChildren, currentFrom);
            currentTo = dom.next(toChildren, currentTo);
            continue;
          }
        }
      }
      let currentFromNext = currentFrom && dom.next(fromChildren, currentFrom);
      patch(currentFrom, currentTo);
      currentTo = currentTo && dom.next(toChildren, currentTo);
      currentFrom = currentFromNext;
    }
    let removals = [];
    while (currentFrom) {
      if (!shouldSkip(removing, currentFrom))
        removals.push(currentFrom);
      currentFrom = dom.next(fromChildren, currentFrom);
    }
    while (removals.length) {
      let domForRemoval = removals.shift();
      domForRemoval.remove();
      removed(domForRemoval);
    }
  }
  function getKey(el) {
    return el && el.nodeType === 1 && key(el);
  }
  function keyToMap(els) {
    let map = {};
    els.forEach((el) => {
      let theKey = getKey(el);
      if (theKey) {
        map[theKey] = el;
      }
    });
    return map;
  }
  function addNodeBefore(children, node, beforeMe) {
    if (!shouldSkip(adding, node)) {
      let clone = node.cloneNode(true);
      children = dom.before(children, beforeMe, clone);
      added(clone);
      return [children, clone];
    }
    return [children, node];
  }
  assignOptions(options);
  fromEl = from;
  toEl = typeof toHtml === "string" ? createElement(toHtml) : toHtml;
  if (window.Alpine && window.Alpine.closestDataStack && !from._x_dataStack) {
    toEl._x_dataStack = window.Alpine.closestDataStack(from);
    toEl._x_dataStack && window.Alpine.clone(from, toEl);
  }
  patch(from, toEl);
  fromEl = void 0;
  toEl = void 0;
  return from;
}
morph.step = () => resolveStep();
morph.log = (theLogger) => {
  logger = theLogger;
};
function shouldSkip(hook, ...args) {
  let skip = false;
  hook(...args, () => skip = true);
  return skip;
}
function initializeAlpineOnTo(from, to, childrenOnly) {
  if (from.nodeType !== 1)
    return;
  if (from._x_dataStack) {
    window.Alpine.clone(from, to);
  }
}

// packages/morph/src/index.js
function src_default(Alpine) {
  Alpine.morph = morph;
}

// packages/morph/builds/module.js
var module_default = src_default;
export {
  module_default as default,
  morph
};
