import { Component, OnChanges, Input, OnInit } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { DomSanitizer } from '@angular/platform-browser';

@Component({
  selector: 'oz-finance-svg-icon',
  template: '<span [innerHTML]="svgIcon"></span>',
  styleUrls: ['./svg-icon.component.scss'],
})
export class SvgIconComponent implements OnInit, OnChanges {
  @Input()
  public name?: string;

  public svgIcon: any;

  constructor(
    private httpClient: HttpClient,
    private sanitizer: DomSanitizer
  ) {
    //
  }

  ngOnInit() {
    if (!this.name) {
      this.svgIcon = '';
      return;
    }
    this.httpClient
      .get(`assets/icons/${this.name}.svg`, { responseType: 'text' })
      .subscribe(value => {
        this.svgIcon = this.sanitizer.bypassSecurityTrustHtml(value);
      });
  }

  public ngOnChanges(): void {
    //
  }
}
