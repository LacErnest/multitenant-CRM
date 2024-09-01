import {
  AfterViewInit,
  Component,
  ElementRef,
  Input,
  OnChanges,
  OnInit,
  SimpleChanges,
  ViewChild,
} from '@angular/core';
import qrcode from 'qrcode-generator';

// https://www.npmjs.com/package/qrcode-generator

@Component({
  selector: 'oz-finance-qr-code',
  templateUrl: './qr-code.component.html',
  styleUrls: ['./qr-code.component.scss'],
})
export class QrCodeComponent implements OnInit, OnChanges, AfterViewInit {
  @Input() dataToEncode: string;
  @Input() type: TypeNumber = 0;
  @Input() correctionLevel: ErrorCorrectionLevel = 'H';

  @ViewChild('qrContainer', { static: false }) qrContainer: ElementRef;

  constructor() {}

  ngOnInit() {}

  ngAfterViewInit() {
    if (this.dataToEncode) {
      this.makeQR();
    }
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (
      qrcode &&
      changes.dataToEncode &&
      !changes.dataToEncode.firstChange &&
      this.dataToEncode
    ) {
      this.makeQR();
    }
  }

  makeQR() {
    const qr = qrcode(this.type, this.correctionLevel);
    qr.addData(this.dataToEncode);
    qr.make();
    this.qrContainer.nativeElement.innerHTML = qr.createSvgTag({ cellSize: 4 });
  }
}
