import { Injectable } from '@angular/core';
import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Observable } from 'rxjs/Observable';
import { of }         from 'rxjs/observable/of';
import { catchError ,tap }         from 'rxjs/operators';

@Injectable()
export class ConnectTaService {
  private Url = "https://www.toernooiklapper.nl/inc-ng/indexN.php";

  constructor(private http : HttpClient) { }



getParms() : Observable<any> {
  const body = { op : 'getparms'};
  return this.http.post(this.Url, body).pipe(
      tap(_ => console.log('bezig met getParms!')),
      catchError(this.handleError('getParms'))
    )
}

submitHost(isProef, gegevens, proefgegevens) : Observable<any> {
  const body = { op : 'incasso', 'isProef': isProef,
    'gegevens' : gegevens,
    'proefgegevens' : proefgegevens};
  return this.http.post(this.Url, body).pipe(
    catchError(this.handleError('submithost'))
  );  
}


/**
 * Handle Http operation that failed.
 * Let the app continue.
 * @param operation - name of the operation that failed
 * @param result - optional value to return as the observable result
 */
private handleError<T> (operation = 'operation', result?: T) {
  return (error: any): Observable<T> => {

    // TODO: send the error to remote logging infrastructure
    console.error(error); // log to console instead

    // TODO: better job of transforming error for user consumption
    console.log(`${operation} failed: ${error.message}`);

    // Let the app keep running by returning an empty result.
    return of(result as T);
  };
}

}
