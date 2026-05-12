import { StrictMode, useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import './style.css';

function App() {
  const [alunni, setAlunni] = useState([]);
  const [errore, setErrore] = useState('');
  const [caricamento, setCaricamento] = useState(true);

  useEffect(() => {
    fetch('/api/alunni')
      .then((response) => {
        if (!response.ok) {
          throw new Error('Risposta API non valida');
        }

        return response.json();
      })
      .then((data) => setAlunni(data))
      .catch((error) => setErrore(error.message))
      .finally(() => setCaricamento(false));
  }, []);

  return (
    <main className="page">
      <section className="panel">
        <p className="eyebrow">React + Slim + MariaDB</p>
        <h1>App fullstack di partenza</h1>
        <p className="intro">
          Il frontend legge i dati dall'API PHP, che a sua volta interroga il database MariaDB.
        </p>

        {caricamento && <p className="status">Caricamento dati...</p>}
        {errore && <p className="status error">Errore: {errore}</p>}

        {!caricamento && !errore && (
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Cognome</th>
              </tr>
            </thead>
            <tbody>
              {alunni.map((alunno) => (
                <tr key={alunno.id}>
                  <td>{alunno.id}</td>
                  <td>{alunno.nome}</td>
                  <td>{alunno.cognome}</td>
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </section>
    </main>
  );
}

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <App />
  </StrictMode>,
);
