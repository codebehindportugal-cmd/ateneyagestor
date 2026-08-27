--Buscar a ultima data de sincronização
select MAX(CDU_DataUltimaSincronizacaoArtigo) as CDU_DataUltimaSincronizacaoArtigo from TDU_RegistosSincronizacao  where CDU_TipoSincronizacao = 'ARTIGOS' 

--Apaga a tabela dos registos de sincronização
Delete from TDU_RegistosSincronizacao where CDU_IdSincronizacao>2




SELECT  Artigo.Artigo, Artigo.Descricao, Artigo.ArtigoPai, Artigo.STKActual, ArtigoMoeda.PVP1,  ArtigoMoeda.PVP2, ArtigoMoeda.PVP3, ArtigoMoeda.PVP4, Artigo.TipoDim1, Artigo.TipoDim2, 
Artigo.Dim1,  Artigo.Dim2, Artigo.RubDim1, Artigo.RubDim2, Artigo.PesoLiquido, Artigo.Peso, Artigo.Volume,  Familias.Descricao as Familia, SubFamilias.Descricao as SubFamilia, Marcas.Descricao as Marca,  
ArtigoIdioma.DescricaoComercial, ArtigoIdioma.Caracteristicas,  TDU_ARM_Armas.CDU_Calibre, Artigo.DataUltimaActualizacao  FROM  Artigo  LEFT OUTER JOIN  TDU_ARM_Armas ON Artigo.Artigo = TDU_ARM_Armas.CDU_CodArtigo  LEFT OUTER JOIN ArtigoMoeda ON Artigo.Artigo = ArtigoMoeda.Artigo  LEFT OUTER JOIN ArtigoIdioma ON Artigo.Artigo = ArtigoIdioma.Artigo  LEFT OUTER JOIN Familias ON Familias.Familia = Artigo.Familia  LEFT OUTER JOIN SubFamilias ON SubFamilias.SubFamilia = Artigo.SubFamilia AND Familias.Familia = Artigo.Familia  LEFT OUTER JOIN Marcas On Marcas.Marca = Artigo.Marca 
WHERE        (Artigo.CDU_WEB = 1)  
AND ArtigoMoeda.PVP2>0 
AND Artigo.Descricao IS NOT NULL 
AND Artigo.Artigo NOT IN (SELECT CDU_CODIGO FROM TDU_RegistosSincronizacao INNER JOIN Artigo A ON A.Artigo = TDU_RegistosSincronizacao.CDU_CODIGO WHERE cast(DataUltimaActualizacao as smalldatetime) <= cast(TDU_RegistosSincronizacao.CDU_DataUltimaSincronizacaoArtigo as smalldatetime) AND CDU_CODIGO is not null) 
order by Artigo.ArtigoPai asc, Artigo.DataUltimaActualizacao asc 

