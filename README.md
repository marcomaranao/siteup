# Xtheme

## Helptext
- Icon: Find a <a href="https://fontawesome.com/icons?d=gallery" target="_blank" title="Font Awesome icons. This link opens in a new tab.">Font Awesome icon</a> and copy the full icon class (e.g. <em>fas fa-chess</em>).

## Theme structure
The folder structure, LESS, markup is based on a combination [BEM](http://getbem.com/) (block, element, modifier) and [atomic design](https://bradfrost.com/blog/post/atomic-web-design/) (atoms, molecules, organisms, pages, templates). For markup, that looks like so:

```
<div class="m-block">
  <div class="m-block__element"></div>
  <div class="m-block__element m-block__element--modifier"></div>
</div>
```
And for styling:

```
.m-block {
  &__element {
    &--modifier {}
  }
}
```

The major benefit to this is that the styling is equivalent to:

```
.m-block {}
.m-block__element {}
.m-block__element--modifier {}
```

but we still get the benefit of nesting the LESS. What this means is that the styling of `.m-block__element` isn't dependent on that element being wrapped by `.m-block`.

### LESS folder structure
The LESS is broken into several folders: `atom`, `base`, `mixins`, `molecule`, `organism`, `page`, `template`, and `third-party`. Within each folder there is a file with the same name (e.g. `module.less`) that gets imported in `styles.less`. The other files in each folder are all prepended with an underscore (_) and are imported by the main file (e.g. `atom.less`).

#### Atom
Small elements, usually only a few lines on HTML at most: button, search trigger, etc.

#### Base
Global elements: typography, tables, etc.

#### Mixins
Colours, flex, vendor-prefixes, etc.

#### Molecule
Bigger than an atom, but shorter than 10-ish lines of HTML: accordion, card, menu, etc.

#### Organism
Elements that are composed of several atoms and/or molecules: accordion group, banner, grid view, list view, etc.

#### Page
For nodes with a body class that affects the design/layout. E.g. `.admin` and `.front`.

#### Template
Layout elements: header, footer, content, etc.

#### Third-party
Any styling associated with third-party components.

## Common layouts and components

### Grid view
Adding `o-grid-view` as the CSS class of a view will add the appropriate styling to the `.view-content` to produce a grid view. The content of each `.views-row` is irrelevant.

### List view
Adding `o-list-view` as the CSS class of a view will add the appropriate styling to each `.views-row` to produce a list view. Adding `o-list-view--simple`, in addition to `o-list-view`, will use a simplied list view styling.

### Card
Cards can receive the `m-card--header-first` class to remove the `order` of `-1` on `.m-card__image`.

```
<div class="m-card">
  <header class="m-card__header"><h2 class="m-card__title"></h2></header>
  <div class="m-card__image"><img src="" /></div>
  <div class="m-card__content-wrapper">
    <p class="m-card__content"></p>
    <div class="m-card__footer"><a href="#" class="m-card__link a-button"></a></div>
  </div>
</div>
```

## Accessibility
The best way to accomplish a good base-state of accessibility is to use proper elements. For example, using a `<button>` for accordion triggers, mobile menu trigger, etc. rather than a `<div>` means that it has a lot of the features we need right out of the box. Rather than writing the trigger function with both `click` and `keypress` defined, we only need `click` because that's triggered on a button element.

- Keyboard nav (`[theme]/js/min/jquery.keynavMenus.min.js` (and in `scripts.js`)): use this to add keyboard nav to menus
- `scripts.js` contains several functions for toggling `aria` attributes
- Templates have been written using proper attributes, elements

### Theming and recommendations
A good general rule is that anything that you can do with a mouse, you should be able to do with a keyboard. That means any new functions you add to scripts should be keyboard tested. It also means that you should specify `:focus` styling when working on `:hover` styling (and `:active`), since elements should visually indicate `:focus`.

Read up on ARIA attributes. Icons without a function are given the `aria-hidden="true"` attribute, since screenreaders don't need to know about them. If the icon is functional, then we can remove this. `aria-label` is used to label elements for screenreaders, which can be important when describing functionality of an interactive element.

`tabindex` is important as well. Values of either `-1` or `0` will be used for elements that should or should not receive focus when tabbing through the page (`-1` means it _won't_ receive focus). For any scripts that manage interactive elements, you'll need to consider toggling these values (and potentially the label) when changing states. As stated above, if you can use proper elements (instead of a `<div>`, for example) you should. That means that `tabindex` may never need to be defined in your markup.

Design should be taking colour contrast into consideration, but if they haven't it's worth noting where contrast fails the [WCAG AA level](https://www.w3.org/WAI/WCAG21/Understanding/contrast-minimum.html). Although most of our sites don't need to meet these requirements, this is a pretty easy one to pass.

**Notice something that's missing in the base state?** Please add it, or ask another dev to review. The more work we can put into this as a starting point, the better.

### Links
- [Examples of common accessible elements](http://web-accessibility.carnegiemuseums.org/code/)
- [Quick intro to basic accessibility for web](https://accessibility.digital.gov/)
- [WCAG 2.1 Guidelines](https://www.w3.org/WAI/WCAG21/quickref/)