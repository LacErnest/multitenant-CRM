import { Injectable } from '@angular/core';
import { ToastrService } from 'ngx-toastr';

@Injectable({
  providedIn: 'root',
})
export class UploadService {
  constructor(private toastrService: ToastrService) {}

  public readFile(file: File, restrictions: FileRestrictions): Promise<any> {
    return new Promise(resolve => {
      const reader = new FileReader();
      reader.onload = () => {
        this.checkFileRestrictions(file, restrictions).then(
          approved => {
            if (approved) {
              resolve({ filename: file.name, file: reader.result });
            } else {
              resolve(false);
            }
          },
          () => {
            resolve(false);
          }
        );
      };

      try {
        reader.readAsDataURL(file);
      } catch (exception) {
        resolve(false);
      }
    });
  }

  private checkFileRestrictions(
    file: File,
    restrictions: FileRestrictions
  ): Promise<boolean> {
    return new Promise(resolve => {
      if (file.size > restrictions.size) {
        this.toastrService.error('Sorry, this file is too big.', 'Error');
        resolve(false);
      }

      if (file.type && !restrictions.allowedFileTypes.includes(file.type)) {
        this.toastrService.error('Sorry, the file type is incorrect.', 'Error');
        resolve(false);
      }

      resolve(true);
    });
  }
}

export interface FileRestrictions {
  size: number;
  allowedFileTypes: string[];
}
