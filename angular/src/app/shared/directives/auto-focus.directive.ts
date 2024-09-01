import { AfterContentInit, Directive, ElementRef, Input } from '@angular/core';

@Directive({
  selector: '[ozFinanceAutoFocus]',
})
export class AutoFocusDirective implements AfterContentInit {
  @Input() autoFocusEnabled = true;
  @Input() autoFocusPreventScroll = true;

  constructor(private el: ElementRef) {}

  ngAfterContentInit(): void {
    if (this.autoFocusEnabled) {
      this.el.nativeElement.focus({
        preventScroll: this.autoFocusPreventScroll,
      });
    }
  }
}
