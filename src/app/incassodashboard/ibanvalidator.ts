import { Injectable} from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { FormControl, AsyncValidator } from '@angular/forms';

import { AbstractControl } from '@angular/forms';

import { Observable } from 'rxjs/Observable';
import { map } from 'rxjs/operators';

@Injectable()
export class IbanValidator {
    constructor(private http : HttpClient) {
    }

    validateIban() {
        return (c : AbstractControl) => {
        let str = c.value.toUpperCase();
        let url = `https://openiban.com/validate/${str}?getBIC=true&validateBankCode=true`;
        return this.http.get(url).pipe(
            map(res => {
                console.log(res);
                return res['valid'] ? null : { ibanInvalid : true};
            }))
        }
    }
}
